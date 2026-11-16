<?php

use App\Models\Indicator;

function indicatorWithTarget(float $target, string $operator = '>='): Indicator
{
    return new Indicator([
        'target_capaian' => $target,
        'target_operator' => $operator,
    ]);
}

it('normalizes overlapping IMUT color bands for minimum targets', function () {
    $indicator = indicatorWithTarget(100);

    expect($indicator->getImutPerformanceColor(100))->toBe('none')
        ->and($indicator->getImutPerformanceColor(81))->toBe('blue')
        ->and($indicator->getImutPerformanceColor(80.99))->toBe('green')
        ->and($indicator->getImutPerformanceColor(71))->toBe('green')
        ->and($indicator->getImutPerformanceColor(70.99))->toBe('yellow')
        ->and($indicator->getImutPerformanceColor(51))->toBe('yellow')
        ->and($indicator->getImutPerformanceColor(50.99))->toBe('orange');
});

it('marks a value at or above its lower target as having no fill', function () {
    $indicator = indicatorWithTarget(80);

    expect($indicator->getImutPerformanceColor(80))->toBe('none')
        ->and($indicator->getImutPerformanceColor(79.99))->toBe('green');
});

it('uses target compliance for inverse indicators', function () {
    $indicator = indicatorWithTarget(5, '<=');

    expect($indicator->getImutPerformanceColor(5))->toBe('none')
        ->and($indicator->getImutPerformanceColor(5.01))->toBe('orange');
});

it('selects RTL and PDSA based on current and previous monthly results', function () {
    $indicator = indicatorWithTarget(80);

    expect($indicator->getMonthlyFollowUpType(null, 60))->toBeNull()
        ->and($indicator->getMonthlyFollowUpType(70, null))->toBe('rtl')
        ->and($indicator->getMonthlyFollowUpType(80, 70))->toBe('pdsa')
        ->and($indicator->getMonthlyFollowUpType(80, 80))->toBeNull()
        ->and($indicator->getMonthlyFollowUpType(70, 90))->toBe('rtl');
});

it('supports inverse targets when deciding whether to create follow-up', function () {
    $indicator = indicatorWithTarget(5, '<=');

    expect($indicator->getMonthlyFollowUpType(6, 8))->toBe('rtl')
        ->and($indicator->getMonthlyFollowUpType(5, 6))->toBe('pdsa')
        ->and($indicator->getMonthlyFollowUpType(5, 4))->toBeNull();
});
