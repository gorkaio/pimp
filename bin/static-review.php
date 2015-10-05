#!/usr/bin/env php
<?php
/**
 * Static review script
 *
 * @author     Gorka López de Torre <gorka@gorka.io>
 * @copyright  2015 Gorka López de Torre
 * @license    MIT
 * @see        http://www.sitepoint.com/writing-php-git-hooks-with-static-review/
 */

const AUTOLOAD_FILE = '/../vendor/autoload.php';

if (!file_exists(__DIR__.AUTOLOAD_FILE)) {
    echo 'You must set up the project dependencies, run the following commands:' . PHP_EOL
        . 'curl -sS https://getcomposer.org/installer | php' . PHP_EOL
        . 'php composer.phar install' . PHP_EOL;
    exit(1);
} else {
    require __DIR__.AUTOLOAD_FILE;
}

use League\CLImate\CLImate;
use StaticReview\Reporter\Reporter;
use StaticReview\Review\Composer\ComposerSecurityReview;
use StaticReview\Review\General\LineEndingsReview;
use StaticReview\Review\PHP\PhpCodeSnifferReview;
use StaticReview\Review\PHP\PhpLeadingLineReview;
use StaticReview\Review\PHP\PhpLintReview;
use StaticReview\StaticReview;
use StaticReview\VersionControl\GitVersionControl;

$reporter = new Reporter();
$climate  = new CLImate();
$git      = new GitVersionControl();
$review = new StaticReview($reporter);

$phpCodeSnifferReview = new PhpCodeSnifferReview();
$phpcsConfig = file_exists(__DIR__.'/../phpcs.xml')?__DIR__.'/../phpcs.xml':null;
if (null !== $phpcsConfig) {
    $phpCodeSnifferReview->setOption('standard', $phpcsConfig);
}
$phpCodeSnifferReview->setOption('ignore', 'spec/*,features/*,tests/*');

$review
    ->addReview(new LineEndingsReview())
    ->addReview(new PhpLeadingLineReview())
    ->addReview(new PhpLintReview())
    ->addReview(new ComposerSecurityReview())
    ->addReview($phpCodeSnifferReview);

$review->files($git->getStagedFiles());

if ($reporter->hasIssues()) {
    foreach ($reporter->getIssues() as $issue) {
        $climate->red($issue);
    }
    $climate->out('')->red('✘ Please fix the errors above');
    exit(1);
} else {
    $climate->out('')->green('✔ Looking good');
    exit(0);
}
