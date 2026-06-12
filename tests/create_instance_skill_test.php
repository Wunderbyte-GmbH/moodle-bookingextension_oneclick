<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace bookingextension_oneclick;

use advanced_testcase;
use bookingextension_agent\local\wbagent\dto\skill_risk_class;
use bookingextension_agent\local\wbagent\skill_contract_validator;
use bookingextension_agent\local\wbagent\skill_registry_factory;
use bookingextension_oneclick\local\wbagent\skills\create_instance_skill;
use context_system;

/**
 * Tests for the oneclick.create_instance agent skill.
 *
 * Network-free: only schema, validation, preflight resolution, preview shaping,
 * the engine contract and discovery are exercised — execute() is only checked on
 * its guard path that never reaches the provisioner.
 *
 * @package    bookingextension_oneclick
 * @category   test
 * @covers     \bookingextension_oneclick\local\wbagent\skills\create_instance_skill
 * @covers     \bookingextension_oneclick\local\wbagent\skill_provider
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class create_instance_skill_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Configure the plugin with a usable minimal config.
     *
     * @return void
     */
    private function configure(): void {
        set_config('enabled', 1, 'bookingextension_oneclick');
        set_config('sharedsecret', 'topsecret', 'bookingextension_oneclick');
        set_config('hostsuffix', 'sofabooking.com', 'bookingextension_oneclick');
        set_config('templates', "sport1, A sports club\nteam2, A team site", 'bookingextension_oneclick');
    }

    /**
     * Identity: name, R3 risk class, mutating (not read-only).
     */
    public function test_identity(): void {
        $skill = new create_instance_skill();
        $this->assertSame('oneclick.create_instance', $skill->get_name());
        $this->assertSame(skill_risk_class::R3, $skill->get_risk_class());
        $this->assertFalse($skill->is_read_only());
    }

    /**
     * The schema description reflects the admin-configured value and lists templates.
     */
    public function test_schema_reflects_config(): void {
        set_config('skilldescription', 'Spin up a demo booking site.', 'bookingextension_oneclick');
        set_config('templates', "sport1, A sports club\nteam2, A team site", 'bookingextension_oneclick');

        $schema = (new create_instance_skill())->get_schema();

        $this->assertSame('Spin up a demo booking site.', $schema['description']);
        $templatedesc = $schema['properties']['template_id']['description'];
        $this->assertStringContainsString('sport1', $templatedesc);
        $this->assertStringContainsString('team2', $templatedesc);
        $this->assertStringContainsString(
            get_string('schema_template_intro', 'bookingextension_oneclick'),
            $templatedesc
        );
        // R3 skills must declare explicit context scopes.
        $this->assertNotEmpty($schema['prompt_meta']['context_scopes']);
    }

    /**
     * Structural validation requires a site name.
     */
    public function test_check_structure(): void {
        $skill = new create_instance_skill();
        $this->assertFalse($skill->check_structure(['sitename' => ''])['valid']);
        $this->assertFalse($skill->check_structure([])['valid']);
        $this->assertTrue($skill->check_structure(['sitename' => 'My Club'])['valid']);
    }

    /**
     * Preflight resolves names and honours a valid template choice.
     */
    public function test_preflight_resolves(): void {
        $this->setAdminUser();
        $this->configure();
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->preflight(
            ['sitename' => 'My Club', 'template_id' => 'team2'],
            $contextid,
            (int)$GLOBALS['USER']->id
        );

        $this->assertSame('pass', $result->status);
        $prepared = $result->preparedinput;
        $this->assertSame('team2', $prepared['template_id']);
        $this->assertSame('My Club', $prepared['sitename']);
        $this->assertSame($prepared['target_release'], $prepared['target_namespace']);
        $this->assertStringEndsWith('.sofabooking.com', $prepared['target_host']);
        $this->assertMatchesRegularExpression('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $prepared['target_release']);
    }

    /**
     * An unknown template asks the user to choose, listing the configured templates.
     */
    public function test_preflight_unknown_template_asks(): void {
        $this->setAdminUser();
        $this->configure();
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->preflight(
            ['sitename' => 'My Club', 'template_id' => 'does-not-exist'],
            $contextid,
            (int)$GLOBALS['USER']->id
        );

        $this->assertSame('hard_block', $result->status);
        $issue = $result->issues[0];
        $this->assertSame('needs_clarification', $issue['severity']);
        // The unknown id and both configured templates are surfaced for the user.
        $this->assertStringContainsString('does-not-exist', $issue['message']);
        $this->assertStringContainsString('sport1', $issue['message']);
        $this->assertStringContainsString('team2', $issue['message']);
    }

    /**
     * A missing template_id asks the user which template they want (no silent default).
     */
    public function test_preflight_missing_template_asks(): void {
        $this->setAdminUser();
        $this->configure();
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->preflight(
            ['sitename' => 'My Club'],
            $contextid,
            (int)$GLOBALS['USER']->id
        );

        $this->assertSame('hard_block', $result->status);
        $issue = $result->issues[0];
        $this->assertSame('needs_clarification', $issue['severity']);
        $this->assertStringContainsString(
            get_string('clarify_template_choose', 'bookingextension_oneclick'),
            $issue['message']
        );
        $this->assertStringContainsString('sport1', $issue['message']);
        $this->assertStringContainsString('team2', $issue['message']);
    }

    /**
     * Preflight hard-blocks when the plugin is not configured.
     */
    public function test_preflight_blocks_when_not_configured(): void {
        $this->setAdminUser();
        // Enabled but no secret/templates.
        set_config('enabled', 1, 'bookingextension_oneclick');
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->preflight(
            ['sitename' => 'My Club'],
            $contextid,
            (int)$GLOBALS['USER']->id
        );

        $this->assertSame('hard_block', $result->status);
    }

    /**
     * Preflight hard-blocks an unconfirmed email address.
     */
    public function test_preflight_blocks_unconfirmed_email(): void {
        $this->configure();
        $user = $this->getDataGenerator()->create_user(['confirmed' => 0]);
        $this->setUser($user);
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->preflight(
            ['sitename' => 'My Club'],
            $contextid,
            (int)$user->id
        );

        $this->assertSame('hard_block', $result->status);
    }

    /**
     * execute() guards on missing configuration without reaching the network.
     */
    public function test_execute_guard_when_not_configured(): void {
        $this->setAdminUser();
        set_config('enabled', 0, 'bookingextension_oneclick');
        $contextid = (int)context_system::instance()->id;

        $result = (new create_instance_skill())->execute(
            ['sitename' => 'My Club', 'template_id' => 'sport1',
                'target_release' => 'trial-x', 'target_namespace' => 'trial-x', 'target_host' => 'trial-x.example'],
            $contextid,
            (int)$GLOBALS['USER']->id
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * Preview descriptor is produced only when a job id is present.
     */
    public function test_get_result_preview(): void {
        $skill = new create_instance_skill();

        $this->assertNull($skill->get_result_preview([], 0, 0));

        $preview = $skill->get_result_preview([
            'oneclick_jobid' => 42,
            'oneclick_host' => 'trial-x.sofabooking.com',
            'oneclick_review' => false,
            'oneclick_eta' => 120,
        ], 0, 0);

        $this->assertIsArray($preview);
        $this->assertSame('oneclick_spawn', $preview['type']);
        $this->assertSame('bookingextension_oneclick/spawn_preview', $preview['js_module']);
        $this->assertSame(42, $preview['payload']['jobid']);
        $this->assertSame('trial-x.sofabooking.com', $preview['payload']['host']);
    }

    /**
     * The skill satisfies the engine's skill contract (so it is registrable).
     */
    public function test_skill_contract_is_valid(): void {
        $skill = new create_instance_skill();
        $component = 'bookingextension/oneclick';

        $capability = skill_contract_validator::build_skill_capability_name($component, $skill->get_name());
        $this->assertSame('bookingextension/oneclick:skill_oneclick_create_instance', $capability);

        $metadata = skill_contract_validator::build_skill_metadata($skill, $component);
        $validation = skill_contract_validator::validate_skill_metadata($metadata);
        $this->assertTrue(
            (bool)$validation['valid'],
            'Skill contract invalid: ' . implode('; ', (array)($validation['errors'] ?? []))
        );
        $this->assertContains($capability, $metadata['capabilities']);
        $this->assertNotEmpty($metadata['context_scopes']);
    }

    /**
     * The agent's registry discovers the skill provider-first.
     */
    public function test_registry_discovers_skill(): void {
        skill_registry_factory::reset();
        $registry = skill_registry_factory::get_default();
        $this->assertNotNull(
            $registry->get_skill('oneclick.create_instance'),
            'oneclick.create_instance should be discovered by the agent skill registry.'
        );
    }
}
