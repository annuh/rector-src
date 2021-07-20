<?php

declare(strict_types=1);

namespace Rector\Core\VersionBonding\Application;

use PHPStan\Php\PhpVersion;
use Rector\Core\Contract\Rector\RectorInterface;
use Rector\Core\Php\PhpVersionProvider;
use Rector\Core\VersionBonding\Contract\MinPhpVersionInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MissedRectorDueVersionChecker
{
    public function __construct(
        private PhpVersionProvider $phpVersionProvider,
        private SymfonyStyle $symfonyStyle,
    ) {
    }

    /**
     * @param RectorInterface[] $rectors
     */
    public function check(array $rectors): void
    {
        $minProjectPhpVersion = $this->phpVersionProvider->provide();

        $missedRectors = $this->resolveMissedRectors($rectors, $minProjectPhpVersion);
        if ($missedRectors === []) {
            return;
        }

        $phpVersion = new PhpVersion($minProjectPhpVersion);
        $warningMessage = sprintf(
            'Your project requires min PHP version "%s". Couple Rectors require higher PHP version and will not run, to avoid breaking your codebase.',
            $phpVersion->getVersionString()
        );

        $this->symfonyStyle->warning($warningMessage);

        $this->symfonyStyle->writeln(' * [PHP-VERSION] Rector rule');

        foreach ($missedRectors as $missedRector) {
            $phpVersion = new PhpVersion($missedRector->provideMinPhpVersion());
            $rectorMessage = sprintf(' * [%s] %s', $phpVersion->getVersionString(), $missedRector::class);
            $this->symfonyStyle->writeln($rectorMessage);
        }

        $solutionMessage = sprintf(
            'Do you want to run them? Either make "require" > "php" in `composer.json` higher,%sor add  "Option::PHP_VERSION_FEATURES" parameter to your `rector.php`.',
            PHP_EOL
        );
        $this->symfonyStyle->note($solutionMessage);

        die;
    }

    /**
     * @param RectorInterface[] $rectors
     * @return MinPhpVersionInterface[]
     */
    private function resolveMissedRectors(array $rectors, int $minProjectPhpVersion): array
    {
        $missedRectors = [];
        foreach ($rectors as $rector) {
            if (! $rector instanceof MinPhpVersionInterface) {
                continue;
            }

            // the conditions are met → skip it
            if ($rector->provideMinPhpVersion() <= $minProjectPhpVersion) {
                continue;
            }

            $missedRectors[] = $rector;
        }

        return $missedRectors;
    }
}
