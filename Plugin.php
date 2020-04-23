<?php namespace Trepmag\Solr;

use Backend;
use System\Classes\PluginBase;
use Event;

/**
 * Solr Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Solr',
            'description' => 'Provides an interface with Solarium for indexing, search and displaying content.',
            'author'      => 'Trepmag',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConsoleCommand('solr.ping', 'Trepmag\Solr\Console\Ping');
        $this->registerConsoleCommand('solr.update_index', 'Trepmag\Solr\Console\UpdateIndex');
        $this->registerConsoleCommand('solr.empty_index', 'Trepmag\Solr\Console\EmptyIndex');
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        parent::boot();
        Event::subscribe(new \Trepmag\Solr\Classes\SearchIndexUpdateEvent);
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            '\Trepmag\Solr\Components\Search' => 'solrSearch',
        ];
    }

    /**
     * Registers any rainlab-page snippets from component.
     *
     * @return array
     */
    public function registerPageSnippets()
    {
        return [
           '\Trepmag\Solr\Components\Search' => 'solrSearch'
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate

        return [
            'trepmag.solr.some_permission' => [
                'tab' => 'Solr',
                'label' => 'Some permission'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return []; // Remove this line to activate

        return [
            'solr' => [
                'label'       => 'Solr',
                'url'         => Backend::url('trepmag/solr/mycontroller'),
                'icon'        => 'icon-leaf',
                'permissions' => ['trepmag.solr.*'],
                'order'       => 500,
            ],
        ];
    }
}
