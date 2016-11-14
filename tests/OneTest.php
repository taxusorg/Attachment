<?php

use \Mockery as m;
use Taxusorg\Attachment\AttachmentAdapter;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Filesystem as Flysystem;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;

class OneTest extends PHPUnit_Framework_TestCase
{
    protected $attachment;
    
    protected $content = 'string content';
  
    public function setUp()
    {
        $this->tempDir = __DIR__.'/tmp';
        mkdir($this->tempDir);
        
        $config = [
            'driver' => 'local',
            'root' => $this->tempDir
        ];
        $permissions = isset($config['permissions']) ? $config['permissions'] : [];
        
        
        $local = new LocalAdapter($config['root'], LOCK_EX, 2, $permissions);
        $filesystem = new Flysystem($local);
        $drive = new FilesystemAdapter($filesystem);
        
        $model = m::mock(Illuminate\Database\Eloquent\Model::class);
        $model->shouldReceive('create')->andReturn(true);
        $model->shouldReceive('where')->andReturnSelf();
        $model->shouldReceive('get')->andReturn([
            'dir' => 'dir',
            'name' => 'test.txt',
            'md5' => md5($this->content),
            'extension' => 'txt',
            'filename' => md5($this->content).'.txt',
        ]);
        
        $this->attachment = new AttachmentAdapter($drive, $model);
    }
    
    public function tearDown()
    {
        $files = new Filesystem();
        $files->deleteDirectory($this->tempDir);
        
    }
    
    public function testGetFilepath()
    {
        $this->assertEquals($this->attachment->getFilepath('dir/test.txt'), 'dir'.DIRECTORY_SEPARATOR.md5($this->content).'.txt');
    }
    
    public function testPut()
    {
        $this->attachment->put('dir/test.txt', $this->content);
        $this->assertStringEqualsFile($this->tempDir.'/dir/'.md5($this->content).'.txt', 'string content');
    }
    
    public function testExists()
    {
        $files = new Filesystem();
        $files->makeDirectory($this->tempDir.'/dir/');
        $files->put($this->tempDir.'/dir/'.md5($this->content).'.txt', $this->content);
        $this->assertEquals($this->attachment->exists('dir/test.txt'), true);
        $files->delete($this->tempDir.'/dir/'.md5($this->content).'.txt');
        $this->assertEquals($this->attachment->exists('dir/test.txt'), false);
    }
}
;