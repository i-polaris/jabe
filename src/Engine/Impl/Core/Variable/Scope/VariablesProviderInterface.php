<?php

namespace BpmPlatform\Engine\Impl\Core\Variable\Scope;

interface VariablesProviderInterface
{
    public function provideVariables(?array $variableNames = []): array;
}
