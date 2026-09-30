<?php

declare(strict_types=1);

namespace CarMoneyLab\Tests\Unit;

use CarMoneyLab\Domain\DecisionEngine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DecisionEngineTest extends TestCase
{
    private DecisionEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new DecisionEngine(
            ['approve_max' => 60.0, 'review_max' => 85.0],
            400000,
        );
    }

    #[DataProvider('ltvValues')]
    public function testDecidesByLtv(float $ltv, int $mileage, string $expected): void
    {
        self::assertSame($expected, $this->engine->decide($ltv, $mileage));
    }

    /** @return array<string,array{float,int,string}> */
    public static function ltvValues(): array
    {
        return [
            'низкий LTV' => [28.5, 96000, DecisionEngine::APPROVE],
            'середина зелёной зоны' => [45.0, 96000, DecisionEngine::APPROVE],
            'сразу под порогом approve' => [59.9, 96000, DecisionEngine::APPROVE],
            'ровно на пороге approve' => [60.0, 96000, DecisionEngine::REVIEW],
            'серая зона' => [72.3, 96000, DecisionEngine::REVIEW],
            'верхняя граница серой зоны' => [85.0, 96000, DecisionEngine::REVIEW],
            'сразу за верхней границей' => [85.01, 96000, DecisionEngine::REJECT],
            'высокий LTV' => [120.0, 96000, DecisionEngine::REJECT],
        ];
    }

    public function testKeepsApproveWhenMileageBelowReviewThreshold(): void
    {
        self::assertSame(DecisionEngine::APPROVE, $this->engine->decide(50.0, 399999));
    }

    public function testKeepsApproveAtExactMileageThreshold(): void
    {
        self::assertSame(DecisionEngine::APPROVE, $this->engine->decide(50.0, 400000));
    }

    public function testSendsToReviewWhenMileageAboveThreshold(): void
    {
        self::assertSame(DecisionEngine::REVIEW, $this->engine->decide(50.0, 400001));
    }

    public function testKeepsReviewWhenMileageAboveThresholdAndLtvInReviewZone(): void
    {
        self::assertSame(DecisionEngine::REVIEW, $this->engine->decide(72.3, 400001));
    }

    public function testKeepsRejectWhenMileageAboveThresholdAndLtvAboveReviewMax(): void
    {
        self::assertSame(DecisionEngine::REJECT, $this->engine->decide(95.0, 400001));
    }
}
