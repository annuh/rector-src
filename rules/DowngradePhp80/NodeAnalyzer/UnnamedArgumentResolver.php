<?php

declare(strict_types=1);

namespace Rector\DowngradePhp80\NodeAnalyzer;

use PhpParser\Node\Arg;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeFunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use Rector\NodeNameResolver\NodeNameResolver;
use ReflectionFunction;

final class UnnamedArgumentResolver
{
    public function __construct(
        private NodeNameResolver $nodeNameResolver
    ) {
    }

    /**
     * @param Arg[] $currentArgs
     * @return Arg[]
     */
    public function resolveFromReflection(
        FunctionReflection | MethodReflection $functionLikeReflection,
        array $currentArgs
    ): array {
        $parametersAcceptor = ParametersAcceptorSelector::selectSingle($functionLikeReflection->getVariants());
        $unnamedArgs = [];
        $parameters = $parametersAcceptor->getParameters();
        $isNativeFunctionReflection = $functionLikeReflection instanceof NativeFunctionReflection;

        if ($isNativeFunctionReflection) {
            $functionLikeReflection = new ReflectionFunction($functionLikeReflection->getName());
        }

        /** @var Arg[] $unnamedArgs */
        $unnamedArgs = [];

        foreach ($currentArgs as $key => $arg) {
            if ($arg->name === null || $this->nodeNameResolver->isName($arg->name, $parameters[$key]->getName())) {
                $unnamedArgs[$key] = new Arg($arg->value, $arg->byRef, $arg->unpack, $arg->getAttributes(), null);
            }
        }

        return $unnamedArgs;
    }
}
