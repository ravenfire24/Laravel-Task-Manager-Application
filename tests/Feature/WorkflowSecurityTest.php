<?php

namespace Tests\Feature;

use Tests\TestCase;

class WorkflowSecurityTest extends TestCase
{
    public function test_ci_actions_are_pinned_to_immutable_commits(): void
    {
        foreach (glob(base_path('.github/workflows/*.yml')) as $path) {
            preg_match_all('/uses:\s*([^\s#]+)/', file_get_contents($path), $matches);
            foreach ($matches[1] as $action) {
                $this->assertMatchesRegularExpression('/@[a-f0-9]{40}$/', $action, basename($path));
            }
        }
    }

    public function test_ci_checks_composer_dependency_advisories(): void
    {
        $this->assertStringContainsString('composer audit --locked', file_get_contents(base_path('.github/workflows/tests.yml')));
    }
}
