<?php
namespace Taxus\Attachment;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application;

class AttachmentServiceProvider extends ServiceProvider
{
    public function boot(Application $app)
    {
        
    }
    
    public function register() {
        $this->app->singleton('attachment', function(){
            return new AttachmentManager($this->app);
        });
    }
}