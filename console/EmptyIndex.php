<?php

namespace Trepmag\Solr\Console;

use Trepmag\Solr\Console\Base;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class EmptyIndex extends Base {

    /**
     * @var string The console command name.
     */
    protected $name = 'solr:empty_index';

    /**
     * @var string The console command description.
     */
    protected $description = 'Empty the Solr server search index.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle() {
        $update = $this->client->createUpdate();
        $update->addDeleteQuery('*:*');
        $update->addCommit();
        $result = $this->client->update($update);

        $this->output->writeln('Query executed:');
        $this->output->block('Query status: ' . $result->getStatus());
        $this->output->block('Query time: ' . $result->getQueryTime());
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments() {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions() {
        return [];
    }

}
