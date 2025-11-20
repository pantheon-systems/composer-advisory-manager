<?php

namespace Pantheon\ComposerAdvisoryManager;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class AdvisoryManagerPlugin implements PluginInterface, EventSubscriberInterface
{
    /** @var bool */
    private $firstRunShown = false;

    public function activate(Composer $composer, IOInterface $io)
    {
        // Automatically disable audit blocking on activation
        // This allows the plugin to install even when vulnerable packages are present
        $config = $composer->getConfig();
        $auditConfig = $config->get('audit') ?? [];

        // Only set if not already configured by user
        if (!array_key_exists('block-insecure', $auditConfig)) {
            $auditConfig['block-insecure'] = false;
            $config->merge([
                'config' => [
                    'audit' => $auditConfig
                ]
            ]);

            $io->writeError(
                '<info>[Pantheon Composer Plugin]</info> Automatically disabled audit blocking to allow installation. '
                . 'This setting will be managed by the plugin going forward.'
            );
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::PRE_INSTALL_CMD => 'disableAuditBlock',
            ScriptEvents::PRE_UPDATE_CMD  => 'disableAuditBlock',
            ScriptEvents::POST_UPDATE_CMD => [
                ['appendIgnoredAdvisories', 0],
                ['displaySecurityAdvisories', -1],
            ],
            ScriptEvents::POST_INSTALL_CMD => 'displaySecurityAdvisories',
        ];
    }

    public function disableAuditBlock(Event $event)
    {
        $composer = $event->getComposer();
        $io       = $event->getIO();
        $config   = $composer->getConfig();

        $auditConfig = $config->get('audit') ?? [];

        // Respect explicit user choice
        if (array_key_exists('block-insecure', $auditConfig)) {
            return;
        }

        // Disable blocking of insecure advisories
        $auditConfig['block-insecure'] = false;
        $config->merge([
            'config' => [
                'audit' => $auditConfig
            ]
        ]);

        $io->writeError(
            '<warning>[Pantheon Composer Plugin]</warning> Security advisories were blocking install/update. '
            . 'For continuity, audit.block-insecure has been automatically disabled. '
            . 'To re-enable strict auditing, add `"block-insecure": true` under `config.audit` in composer.json.'
        );

        $this->printRemediation($event);
    }

    private function printRemediation(Event $event)
    {
        $io = $event->getIO();

        if ($this->firstRunShown) {
            return;
        }
        $this->firstRunShown = true;

        $io->writeError("\n<info>[Pantheon] Recommended Remediation:</info>");
        $io->writeError("Composer encountered blocked dependencies due to security advisories.");
        $io->writeError("You should plan to upgrade the affected packages to remove the advisory ignore list.");
        $io->writeError("Common example upgrades:");
        $io->writeError("  - twig/twig → ^3.22");
        $io->writeError("  - symfony/process → ^5.4.47");
        $io->writeError("  - consolidation/robo → latest compatible release");
        $io->writeError("This plugin keeps your build unblocked now — but it does not replace patching.\n");
    }

    public function appendIgnoredAdvisories(Event $event)
    {
        $composer = $event->getComposer();
        $io       = $event->getIO();

        $cmd = 'composer audit --format=json 2>&1';
        $io->writeError('<comment>[Pantheon]</comment> Running `composer audit` to detect new advisories to ignore...');
        $output = [];
        $return = null;
        exec($cmd, $output, $return);

        // Note: composer audit returns non-zero when advisories are found, which is expected
        if (empty($output)) {
            $io->writeError('<comment>[Pantheon]</comment> Could not get audit results — skipping advisory auto-ignore.');
            return;
        }

        $json = @json_decode(implode("\n", $output), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $io->writeError('<comment>[Pantheon]</comment> Could not parse audit JSON — skipping advisory auto-ignore.');
            return;
        }

        $advisories = $json['advisories'] ?? [];
        if (empty($advisories)) {
            $io->writeError('<comment>[Pantheon]</comment> No security advisories detected.');
            return;
        }

        $config = $composer->getConfig();
        $auditConfig = $config->get('audit') ?? [];
        $existingIgnored = $auditConfig['ignore'] ?? [];

        $foundIds = array_keys($advisories);
        $newIgnore = array_values(array_unique(array_merge($existingIgnored, $foundIds)));

        if ($newIgnore === $existingIgnored) {
            $io->writeError('<comment>[Pantheon]</comment> Advisory IDs already ignored.');
            return;
        }

        // Update in-memory config
        $auditConfig['ignore'] = $newIgnore;
        $config->merge([
            'config' => [
                'audit' => $auditConfig
            ]
        ]);

        // Persist to composer.json using Composer's config source
        $configSource = $composer->getConfig()->getConfigSource();
        $configSource->addConfigSetting('audit.ignore', $newIgnore);

        $added = array_diff($newIgnore, $existingIgnored);
        foreach ($added as $id) {
            $io->writeError("<comment>[Pantheon]</comment> Added advisory ID to ignore list: {$id}");
        }
    }

    public function displaySecurityAdvisories(Event $event)
    {
        $io = $event->getIO();

        // Run composer audit to get detailed advisory information
        $cmd = 'composer audit --format=summary 2>&1';
        $output = [];
        $return = null;
        exec($cmd, $output, $return);

        if (empty($output)) {
            return;
        }

        $outputStr = implode("\n", $output);

        // Check if there are actual advisories (not just "No security vulnerability advisories found")
        if (stripos($outputStr, 'No security vulnerability advisories found') !== false) {
            return;
        }

        // Display a prominent security advisory notice
        $io->writeError("");
        $io->writeError("<warning>╔═══════════════════════════════════════════════════════════════╗</warning>");
        $io->writeError("<warning>║  🔒 SECURITY ADVISORIES DETECTED (Non-blocking)              ║</warning>");
        $io->writeError("<warning>╚═══════════════════════════════════════════════════════════════╝</warning>");
        $io->writeError("");

        // Display the audit summary
        foreach ($output as $line) {
            if (trim($line) !== '') {
                $io->writeError("  " . $line);
            }
        }

        $io->writeError("");
        $io->writeError("<info>ℹ️  These advisories have been auto-ignored to allow builds to continue.</info>");
        $io->writeError("<info>   Run 'composer audit' for full details.</info>");
        $io->writeError("<info>   Please plan to upgrade affected packages as soon as possible.</info>");
        $io->writeError("");
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // no-op
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // no-op
    }
}
