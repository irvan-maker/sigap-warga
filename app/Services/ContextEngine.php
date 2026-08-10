<?php

namespace App\Services;

use App\Context\ContextEngineResult;
use App\Context\EntryContext;

/**
 * Resolves context readiness without selecting a service or workflow.
 *
 * Final service territory is intentionally deferred. Future quick reports
 * and urgent or emergency needs may require different territory and workflow
 * rules after the intent is understood.
 */
class ContextEngine
{
    public function __construct(
        private readonly ContextResolver $contextResolver,
        private readonly ContextReadinessPolicy $contextReadinessPolicy,
        private readonly ContextGuidanceService $contextGuidanceService,
    ) {}

    public function resolve(EntryContext $entry): ContextEngineResult
    {
        $context = $this->contextResolver->resolve($entry);
        $readiness = $this->contextReadinessPolicy->evaluate($context);
        $guidance = $this->contextGuidanceService->decide($readiness);

        return new ContextEngineResult($context, $guidance);
    }
}
