<?php
namespace Taxusorg\Attachment;

use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AttachmentManager
{

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;
    
    
    /**
     * The disk.
     * 
     * @param unknown $app
     */
    protected $disks = [];
    
    /**
     * Create
     * 
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }
    
    /**
     * Get an instance
     * 
     * @param string $name
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function disk($name = null)
    {
        $name = $name ?  : $this->getDefaultDisk();
        
        return $this->get($name);
    }
    
    /**
     * Get the disk from the local cache.
     * 
     * @param string $name
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected function get($name)
    {
        return isset($this->disks[$name]) ? $this->disks[$name] : $this->resolveDisk($name);
    }
    
    /**
     * Resolve a disk
     * 
     * @param string $name
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected function resolveDisk($name)
    {
        $config = $this->getConfig($name);
        
        $model = new $config['model'];
        
        return $this->adapt($this->createDisk($config['filesystem']), $model);
    }
    
    /**
     * Create an instance
     * 
     * @return
     */
    protected function createDisk($name)
    {
        return Storage::disk($name);
    }
    
    /**
     * Create a model
     */
    protected function createModel()
    {
        
    }
    
    /**
     * Adapt
     */
    protected function adapt(FilesystemContract $disk,Model $model)
    {
        return new AttachmentAdapter($disk, $model);
    }
    

    /**
     * Get the filesystem connection configuration.
     *
     * @param string $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["attachments.disks.{$name}"];
    }
    

    /**
     * Get the default disk name.
     *
     * @return string
     */
    public function getDefaultDisk()
    {
        return $this->app['config']['attachments.default'];
    }
    
    /**
     * Dynamically call the default disk instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->disk(), $method], $parameters);
    }    
}