<?php

use \Mockery as m;
use Taxus\Attachment\AttachmentAdapter;

class OneTest extends PHPUnit_Framework_TestCase
{
    protected $attachment;
    
    public function __construct()
    {
        $disk = m::mock('Filesystem', 'Illuminate\Contracts\Filesystem\Filesystem');
        $disk->shouldReceive('put')->andReturn(true);
        $model = m::mock('Model', 'Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('create')->andReturn(true);
        
        $this->attachment = new AttachmentAdapter($disk, $model);
    }
    
    public function tearDown()
    {
        m::close();
    }
    
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testPut()
    {
        
        $this->attachment->put('test.txt', fopen('example.txt', 'w+'));
        $this->attachment->put('test.txt', file_get_contents('example.txt'));
        $this->assertTrue(true);
    }
}
;