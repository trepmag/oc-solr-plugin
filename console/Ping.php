<?php

namespace Trepmag\Solr\Console;

use Trepmag\Solr\Console\Base;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Ping extends Base {

    /**
     * @var string The console command name.
     */
    protected $name = 'solr:ping';

    /**
     * @var string The console command description.
     */
    protected $description = 'Check the connection to the Solr server and the health of the Solr server.';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle() {
        $ping = $this->client->createPing();
        try {
            $result = $this->client->ping($ping);
            $this->output->writeln('Ping query successful:');
            $this->output->block(json_encode($result->getData()));
        } catch (\Solarium\Exception\HttpException $e) {
            $this->output->writeln('Ping query failed:');
            $this->output->block($e->getStatusMessage());
        }

        $query = $this->client->createQuery($this->client::QUERY_SELECT);
        try {
            $resultset = $this->client->execute($query);
            $this->output->block('Num Docs: '. $resultset->getNumFound());
        } catch (\Solarium\Exception\HttpException $e) {
            $this->output->block($e->getStatusMessage());
        }
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
