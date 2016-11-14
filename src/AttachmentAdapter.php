<?php
namespace Taxus\Attachment;

use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Taxus\Attachment\Model;

class AttachmentAdapter implements FilesystemContract
{
    protected $disk;
    protected $model;

    /**
     * Create a new filesystem adapter instance.
     *
     * @param Illuminate\Contracts\Filesystem\Filesystem $disk
     * @return void
     */
    public function __construct(FilesystemContract $disk,Model $model)
    {
        $this->disk = $disk;
        $this->model = $model;
    }
    
    protected function explainPath($path)
    {
        $pathinfo = pathinfo($path);
        $pathinfo['dirname'] = substr($pathinfo['dirname'], 0, 1) == '/' ? substr($pathinfo['dirname'], 1) : $pathinfo['dirname'];
        return $pathinfo;
    }
    
    /**
     * 
     * @param string $path
     * @return string|boolean
     */
    public function getFilepath($path)
    {
        $result = $this->getModelByPath($path);
        
        if($result) {
            return $result['dir'].DIRECTORY_SEPARATOR.$result['filename'];
        }
        
        return false;
    }
    
    /**
     * 
     * @param string $path
     * @return model|false
     */
    public function getModelByPath($path)
    {
        $pathinfo = $this->explainPath($path);
        
        $result = $this->model->where([
            'name' => $pathinfo['basename'],
            'dir' => $pathinfo['dirname'],
        ])->first();
        
        return $result ?: false;
    }
    
    /**
     * Determine if a file exists.
     *
     * @param string $path            
     * @return bool
     */
    public function exists($path)
    {
        return $this->disk->exists($this->getFilepath($path)) ? 1 : 0;
    }

    /**
     * Get the contents of a file.
     *
     * @param string $path            
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function get($path)
    {
        return $this->disk->get($this->getFilepath($path));
    }

    /**
     * Write the contents of a file.
     *
     * @param string $path            
     * @param string|resource $contents
     * @param string $visibility            
     * @return bool
     */
    public function put($path, $contents, $visibility = null)
    {
        $pathinfo = $this->explainPath($path);
        
        if(is_resource($contents)) {
            $md5 = md5(stream_get_contents($contents));
        }else{
            $md5 = md5($contents);
        }
        
        $filename = $md5.'.'.$pathinfo['extension'];
        $filepath = $pathinfo['dirname'].DIRECTORY_SEPARATOR.$filename;
        while ($this->disk->exists($filepath)) {
            $filename = $md5.'-'.rand(0,1000).'.'.$pathinfo['extension'];
            $filepath = $pathinfo['dirname'].DIRECTORY_SEPARATOR.$filename;
        }
        
        $result = $this->disk->put($filepath, $contents, $visibility);
        if(!$result) return false;
        
        $size = $this->disk->size($filepath);
        $this->model->create([
            'name' => $pathinfo['basename'],
            'dir' => $pathinfo['dirname'],
            'md5' => $md5,
            'extension' => $pathinfo['extension'],
            'filename' => $filename,
            'size' => $size,
        ]);
        
        return true;
    }
    
    /**
     * Get the visibility for the given path.
     *
     * @param string $path
     * @return string
     */
    public function getVisibility($path)
    {
        return $this->disk->getVisibility($this->getFilepath($path));
    }

    /**
     * Set the visibility for the given path.
     *
     * @param string $path
     * @param string $visibility            
     * @return void
     */
    public function setVisibility($path, $visibility)
    {
        return $this->disk->setVisibility($this->getFilepath($path), $visibility);
    }

    /**
     * Prepend to a file.
     *
     * @param string $path
     * @param string $data            
     * @return int
     */
    public function prepend($path, $data)
    {
        return $this->disk->prepend($this->getFilepath($path), $data);
    }

    /**
     * Append to a file.
     *
     * @param string $path
     * @param string $data
     * @return int
     */
    public function append($path, $data)
    {
        return $this->disk->append($this->getFilepath($path), $data);
    }

    /**
     * Delete the file at a given path.
     *
     * @param string|array $paths
     * @return bool
     */
    public function delete($path)
    {
        $this->getModelByPath($path)->delete();
        return $this->disk->delete($this->getFilepath($path));
    }

    /**
     * Copy a file to a new location.
     *
     * @param string $from            
     * @param string $to            
     * @return bool
     */
    public function copy($from, $to)
    {
        return $this->disk->copy($from, $to);
    }

    /**
     * Move a file to a new location.
     *
     * @param string $from            
     * @param string $to            
     * @return bool
     */
    public function move($from, $to)
    {
        return $this->disk->move($from, $to);
    }

    /**
     * Get the file size of a given file.
     *
     * @param string $path            
     * @return int
     */
    public function size($path)
    {
        return $this->disk->size($this->getFilepath($path));
    }

    /**
     * Get the file's last modification time.
     *
     * @param string $path            
     * @return int
     */
    public function lastModified($path)
    {
        return $this->lastModified($this->getFilepath($path));
    }

    /**
     * Get an array of all files in a directory.
     *
     * @param string|null $directory            
     * @param bool $recursive            
     * @return array
     */
    public function files($directory = null, $recursive = false)
    {
        return $this->disk->files($directory, $recursive);
    }

    /**
     * Get all of the files from the given directory (recursive).
     *
     * @param string|null $directory            
     * @return array
     */
    public function allFiles($directory = null)
    {
        return $this->disk->allFiles($directory);
    }

    /**
     * Get all of the directories within a given directory.
     *
     * @param string|null $directory            
     * @param bool $recursive            
     * @return array
     */
    public function directories($directory = null, $recursive = false)
    {
        return $this->disk->directories($directory, $recursive);
    }

    /**
     * Get all (recursive) of the directories within a given directory.
     *
     * @param string|null $directory            
     * @return array
     */
    public function allDirectories($directory = null)
    {
        return $this->disk->allDirectories($directory);
    }

    /**
     * Create a directory.
     *
     * @param string $path            
     * @return bool
     */
    public function makeDirectory($path)
    {
        return $this->disk->makeDirectory($path);
    }

    /**
     * Recursively delete a directory.
     *
     * @param string $directory            
     * @return bool
     */
    public function deleteDirectory($directory)
    {
        return $this->disk->deleteDirectory($directory);
    }
    
    public function applyPathPrefix($path)
    {
        return $this->disk->getAdapter()->applyPathPrefix($path);
    }
}