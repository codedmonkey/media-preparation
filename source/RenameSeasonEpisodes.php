<?php
/*
 * (c) Tim Goudriaan <tim@codedmonkey.com>
 */

namespace App;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class RenameSeasonEpisodes
{
    public function execute(InputInterface $input, OutputStyle $output, string $directory)
    {
        $filesystem = new Filesystem;
        $finder = (new Finder)
            ->files()
            ->in($directory)
            ->depth(0)
        ;

        $files = [];
        foreach ($finder as $metadata) {
            /** @var \SplFileInfo $metadata */
            if (!preg_match('/S(\d*)E(\d*)/', $metadata->getFilename(), $matches)) {
                continue;
            }

            list(, $season, $episode) = $matches;

            $file = [
                'metadata' => $metadata,
                'season' => $season,
                'episode' => $episode,
                'output' => sprintf('s%se%s', $season, $episode),
            ];

            $files[] = $file;
        }

        if (!count($files)) {
            $output->warning('No episode files found in this directory.');

            return;
        }

        $matched = array_unique(array_map(function($metadata) {
            return $metadata['output'];
        }, $files));

        sort($matched);

        $output->writeln(sprintf('Matched episodes: %s', implode(', ', $matched)));
        $output->newLine();

        $question = new Question('Enter the show name: ');

        $show = (new QuestionHelper)->ask($input, $output, $question);
        $output->newLine();

        foreach ($files as $file) {
            $metadata = $file['metadata'];

            $filename = sprintf('%s - %s.%s', $show, $file['output'], $metadata->getExtension());

            if ($output->isVerbose()) {
                $output->writeln(sprintf('<info>Renaming %s to %s</info>', $metadata->getFilename(), $filename));
            }

            $source = $metadata->getPathname();
            $target = implode('/', [$metadata->getPath(), $filename]);

            $filesystem->rename($source, $target);
        }
    }
}
