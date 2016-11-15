<?php

use \Mockery as m;
use Taxusorg\Attachment\AttachmentAdapter;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Filesystem as Flysystem;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Database\Eloquent\Builder; 
use Illuminate\Database\Eloquent\Relations\Relation; 

class OneTest extends PHPUnit_Framework_TestCase
{
    protected $attachment;
    
    protected $content = 'Hello world!';
  
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
        
        $model = new EloquentModelStub();
        
        $this->attachment = new AttachmentAdapter($drive, $model);
    }
    
    public function tearDown()
    {
        Illuminate\Database\Eloquent\Model::unsetEventDispatcher();
        Carbon\Carbon::resetToStringFormat();
        
        
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
        $this->assertStringEqualsFile($this->tempDir.'/dir/'.md5($this->content).'.txt', $this->content);
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
    
    public function testGet()
    {
        $files = new Filesystem();
        $files->makeDirectory($this->tempDir.'/dir/');
        $files->put($this->tempDir.'/dir/'.md5($this->content).'.txt', $this->content);
        $this->assertEquals($this->attachment->get('dir/test.txt'), $this->content);
        $this->attachment->delete('dir/test.txt');
        $this->assertEquals($this->attachment->exists('dir/test.txt'), false);
    }
    
    public function testFiles()
    {
        /*$files = new Filesystem();
        $files->makeDirectory($this->tempDir.'/dir/foo/');
        $files->put($this->tempDir.'/dir/'.md5($this->content).'.txt', $this->content);
        $files->put($this->tempDir.'/dir/foo/'.md5($this->content).'.txt', $this->content);*/
        $this->attachment->put('dir/test.txt', $this->content);
        $this->attachment->put('dir/test.txt', $this->content);
        print_r($this->attachment->files('dir', true));
    }
}

class EloquentModelStub extends Model
{
    public $connection;
    public $scopesCalled = [];
    protected $table = 'foo_table';
    protected $guarded = [];
    protected $morph_to_stub_type = 'EloquentModelSaveStub';

    public function getListItemsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setListItemsAttribute($value)
    {
        $this->attributes['list_items'] = json_encode($value);
    }

    public function getPasswordAttribute()
    {
        return '******';
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password_hash'] = sha1($value);
    }

    public function publicIncrement($column, $amount = 1)
    {
        return $this->increment($column, $amount);
    }

    public function belongsToStub()
    {
        return $this->belongsTo('EloquentModelSaveStub');
    }

    public function morphToStub()
    {
        return $this->morphTo();
    }

    public function belongsToExplicitKeyStub()
    {
        return $this->belongsTo('EloquentModelSaveStub', 'foo');
    }

    public function incorrectRelationStub()
    {
        return 'foo';
    }

    public function getDates()
    {
        return [];
    }

    public function getAppendableAttribute()
    {
        return 'appended';
    }

    public function scopePublished(Builder $builder)
    {
        $this->scopesCalled[] = 'published';
    }

    public function scopeCategory(Builder $builder, $category)
    {
        $this->scopesCalled['category'] = $category;
    }

    public function scopeFramework(Builder $builder, $framework, $version)
    {
        $this->scopesCalled['framework'] = [$framework, $version];
    }
    
    public function save(array $options = [])
    {
        return true;
    }
    
    public function delete()
    {
        return true;
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        date_default_timezone_set('PRC');
        $builder = m::mock('Illuminate\Database\Query\Builder');
        $builder->shouldReceive('from')->with('foo_table');
        $builder->shouldReceive('where')->andReturnSelf();
        $builder->shouldReceive('take')->andReturnSelf();
        $builder->shouldReceive('get')->andReturn([
            [
                'dir' => 'dir',
                'name' => 'test.txt',
                'md5' => md5('Hello world!'),
                'extension' => 'txt',
                'filename' => md5('Hello world!').'.txt',
            ],
        ]);
        
        
        /*$grammar = new Illuminate\Database\Query\Grammars\Grammar;
        $processor = m::mock('Illuminate\Database\Query\Processors\Processor');

        return new Illuminate\Database\Query\Builder(m::mock('Illuminate\Database\ConnectionInterface'), $grammar, $processor);
        */
        
        return $builder;
    }
    
}
