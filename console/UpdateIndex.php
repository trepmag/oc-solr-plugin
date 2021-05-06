<?php

namespace Trepmag\Solr\Console;

use Trepmag\Solr\Console\Base;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Config;

class UpdateIndex extends Base {

    /**
     * @var string The console command name.
     */
    protected $name = 'solr:index-update';

    /**
     * @var string The console command description.
     */
    protected $description = 'Update the Solr server search index.';

    protected function configure()
    {
        $this->setAliases([
            'solr:update_index',
        ]);
    }

    /**
     * Execute the console command.
     * @return void
     */
    public function handle() {

        $update = $this->client->createUpdate();

        $total = 0;
        $i = 1;
        $this->output->writeln('Objects to update:');
        foreach (Config::get('solr.search_index_classes', []) as $class) {
            $index = new $class;
            $objects = $index->getObjects();
            $count = count($objects);
            $this->output->block($class . ': ' . $count);
            $total += $count;
            foreach ($objects as $object) {
                $doc = $update->createDocument();
                $documents[] = $index->buildDoc($object, $doc);
                if ($i % $this->option('batch-size') == 0 || $i == $total) {
                    $update->addDocuments($documents);
                    $documents = [];
                }
                $i++;
            }
        }
        $update->addCommit();
        $result = $this->client->update($update);

        $this->output->writeln('Updated executed:');
        $this->output->block('Query status: ' . $result->getStatus());
        $this->output->block('Query time: ' . $result->getQueryTime());
        $this->output->block('Total: ' . $total);
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments() {
        return [
        ];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions() {
        return [
            ['batch-size', 'bs', InputOption::VALUE_OPTIONAL, 'Batch size', 100],
        ];
    }

}
