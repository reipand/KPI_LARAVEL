<?php

namespace Tests\Unit;

use App\Models\KpiIndicator;
use App\Services\KpiFormulaEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KpiFormulaEngineTest extends TestCase
{
    private KpiFormulaEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new KpiFormulaEngine();
    }

    // ──────────────────────────────────────────────────────────────
    // Helper: build a KpiIndicator model without DB
    // ──────────────────────────────────────────────────────────────

    private function indicator(float $weight, array $formula): KpiIndicator
    {
        $m = new KpiIndicator();
        $m->weight  = $weight;
        $m->formula = $formula;

        return $m;
    }

    // ──────────────────────────────────────────────────────────────
    // 1. Percentage formula
    // ──────────────────────────────────────────────────────────────

    public function test_percentage_proportional_score(): void
    {
        $ind = $this->indicator(40, ['type' => 'percentage']);
        $this->assertEquals(20.0, $this->engine->evaluate($ind, 10, 20));
    }

    public function test_percentage_full_score_when_at_target(): void
    {
        $ind = $this->indicator(50, ['type' => 'percentage']);
        $this->assertEquals(50.0, $this->engine->evaluate($ind, 100, 100));
    }

    public function test_percentage_capped_at_weight(): void
    {
        $ind = $this->indicator(30, ['type' => 'percentage']);
        // actual > target → capped at weight
        $this->assertEquals(30.0, $this->engine->evaluate($ind, 150, 100));
    }

    public function test_percentage_zero_when_target_is_zero(): void
    {
        $ind = $this->indicator(50, ['type' => 'percentage']);
        $this->assertEquals(0.0, $this->engine->evaluate($ind, 0, 0));
    }

    // ──────────────────────────────────────────────────────────────
    // 2. Conditional formula
    // ──────────────────────────────────────────────────────────────

    public function test_conditional_full_when_actual_meets_target(): void
    {
        $ind = $this->indicator(50, ['type' => 'conditional']);
        $this->assertEquals(50.0, $this->engine->evaluate($ind, 100, 100));
    }

    public function test_conditional_proportional_when_below_target(): void
    {
        $ind = $this->indicator(50, ['type' => 'conditional']);
        $this->assertEquals(25.0, $this->engine->evaluate($ind, 50, 100));
    }

    public function test_conditional_full_when_target_is_zero_but_actual_positive(): void
    {
        $ind = $this->indicator(20, ['type' => 'conditional']);
        $this->assertEquals(20.0, $this->engine->evaluate($ind, 5, 0));
    }

    // ──────────────────────────────────────────────────────────────
    // 3. Zero penalty formula
    // ──────────────────────────────────────────────────────────────

    public function test_zero_penalty_full_when_no_violations(): void
    {
        $ind = $this->indicator(35, ['type' => 'zero_penalty']);
        $this->assertEquals(35.0, $this->engine->evaluate($ind, 0, 0));
    }

    public function test_zero_penalty_zero_when_any_violation(): void
    {
        $ind = $this->indicator(35, ['type' => 'zero_penalty']);
        $this->assertEquals(0.0, $this->engine->evaluate($ind, 1, 0));
    }

    public function test_zero_penalty_zero_when_multiple_violations(): void
    {
        $ind = $this->indicator(35, ['type' => 'zero_penalty']);
        $this->assertEquals(0.0, $this->engine->evaluate($ind, 5, 0));
    }

    // ──────────────────────────────────────────────────────────────
    // 4. Threshold formula
    // ──────────────────────────────────────────────────────────────

    public static function thresholdProvider(): array
    {
        $thresholds = KpiFormulaEngine::defaultThresholds();

        return [
            'exactly 100% → 100 score_pct' => [100, 100, 50, $thresholds, 50.0],
            '95% → 80 score_pct'            => [95,  100, 50, $thresholds, 40.0],
            '75% → 60 score_pct'            => [75,  100, 50, $thresholds, 30.0],
            '55% → 40 score_pct'            => [55,  100, 50, $thresholds, 20.0],
            '10% → 0 score_pct'             => [10,  100, 50, $thresholds, 0.0],
        ];
    }

    #[DataProvider('thresholdProvider')]
    public function test_threshold_steps(
        float $actual,
        float $target,
        float $weight,
        array $thresholds,
        float $expected,
    ): void {
        $ind = $this->indicator($weight, ['type' => 'threshold', 'thresholds' => $thresholds]);
        $this->assertEquals($expected, $this->engine->evaluate($ind, $actual, $target));
    }

    public function test_threshold_empty_array_falls_back_to_percentage(): void
    {
        $ind = $this->indicator(40, ['type' => 'threshold', 'thresholds' => []]);
        // Falls back to percentage: 10/20 * 40 = 20
        $this->assertEquals(20.0, $this->engine->evaluate($ind, 10, 20));
    }

    // ──────────────────────────────────────────────────────────────
    // 5. Flat formula
    // ──────────────────────────────────────────────────────────────

    public function test_flat_score_uses_multiplier(): void
    {
        $ind = $this->indicator(50, ['type' => 'flat', 'score' => 0.8]);
        $this->assertEquals(40.0, $this->engine->evaluate($ind, 0, 0));
    }

    public function test_flat_score_defaults_to_full_weight_when_score_omitted(): void
    {
        $ind = $this->indicator(50, ['type' => 'flat']);
        $this->assertEquals(50.0, $this->engine->evaluate($ind, 0, 0));
    }

    // ──────────────────────────────────────────────────────────────
    // 6. Edge cases
    // ──────────────────────────────────────────────────────────────

    public function test_zero_weight_always_returns_zero(): void
    {
        foreach (['percentage', 'conditional', 'zero_penalty', 'flat'] as $type) {
            $ind = $this->indicator(0, ['type' => $type]);
            $this->assertEquals(0.0, $this->engine->evaluate($ind, 100, 100), "Type: $type should return 0 for 0 weight");
        }
    }

    public function test_achievement_ratio_percentage(): void
    {
        $ind = $this->indicator(50, ['type' => 'percentage']);
        $this->assertEquals(0.5, $this->engine->achievementRatio($ind, 50, 100));
    }

    public function test_achievement_ratio_capped_at_one(): void
    {
        $ind = $this->indicator(50, ['type' => 'percentage']);
        $this->assertEquals(1.0, $this->engine->achievementRatio($ind, 200, 100));
    }

    public function test_default_formula_type_when_null(): void
    {
        $ind = $this->indicator(30, []);   // no 'type' key
        // Should default to 'percentage': 5/10 * 30 = 15
        $this->assertEquals(15.0, $this->engine->evaluate($ind, 5, 10));
    }
}
