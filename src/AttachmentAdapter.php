<?php
namespace Taxusorg\Attachment;

use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Database\Eloquent\Model;
use League\Flysystem\Util;

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
    
    public function explainPath($path)
    {
        $pathinfo = Util::pathinfo($path);
        $pathinfo['dirname'] = $this->normalizeDirname($pathinfo['dirname']);
        return $pathinfo;
    }
    
    public function normalizeDirname($dir)
    {
        if ($dir == '/') return '';
        return substr($dir, 0, 1) == '/' ? substr($dir, 1) : $dir;
    }
    
    /**
     * 
     * @param string $path
     * @return string|boolean
     */
    public function getFilepath($path)
    {
        $result = $this->getBuilderByPaths($path)->first();
        
        return $result ? $result['dir'].DIRECTORY_SEPARATOR.$result['filename'] : false;
    }
    
    /**
     * 
     * @param string|array $paths
     * @return array|boolean
     */
    public function getFilepaths($paths)
    {
        $results = $this->getBuilderByPaths($paths)->get();
        if(!$results) return false;
        
        $array = array();
        foreach($results as $result) {
            $array[] = $result['dir'].DIRECTORY_SEPARATOR.$result['filename'];
        }
        
        return $array;
    }
    
    public function getFilepathsByDirs($dirs)
    {
        $results = $this->getBuilderByDirs($dirs)->get();
        if(!$results) return false;
        
        $array = array();
        foreach($results as $result) {
            $array[] = $result['dir'].DIRECTORY_SEPARATOR.$result['filename'];
        }
    }
    
    /**
     * 
     * @param string $filepath
     * @return string|boolean
     */
    public function getPath($filepath)
    {
        $result = $this->getBuilderByFilepaths($filepath)->first();
        
        return $result ? $result['dir'].'/'.$result['name'] : false;
    }
    
    /**
     * 
     * @param array|string $filepaths
     * @return string|boolean
     */
    public function getPaths($filepaths)
    {
        $results = $this->getBuilderByFilepaths($filepaths)->get();
        if(!$results) return false;
        
        $array = array();
        foreach ($results as $result){
            $array[] = $result == '' ? $result['dir'].'/'.$result['name'] : $result['name'];
        }
        
        return $array;
    }
    
    public function getPathsByDirs($dirs)
    {
        $results = $this->getBuilderByDirs($dirs)->get();
        if(!$results) return false;
        
        $array = array();
        foreach ($results as $result) {
            $array[] = $result['dir'].'/'.$result['name'];
        }
        
        return $array;
    }
    
    /**
     * 
     * @param string|array $paths
     * @return boolean
     */
    public function deletePaths($paths)
    {
        return $results = $this->getBuilderByPaths($paths)->delete();
    }
    
    public function deletePathsByDirs($dirs)
    {
        return $results = $this->getBuilderByDirs($dirs)->delete();
    }
    
    public function getDirs($dirs)
    {
        $results = $this->getBuilderByDirs($dirs)->groupBy('dir')->get(['dir']);
        if(!$results) return false;
        
        $array = array();
        foreach ($results as $result) {
            $array[] = $result['dir'];
        }
        
        return $array;
    }
    
    /**
     * 
     * @param array|string $paths
     */
    public function getBuilderByPaths($paths)
    {
        is_array($paths) || $paths = [$paths];
        
        $query = $this->model->newQuery();
        foreach ($paths as $path) {
            $pathinfo = $this->explainPath($path);
            $query->orWhere([
                'name' => $pathinfo['basename'],
                'dir' => $pathinfo['dirname'],
            ]);
        }
        
        return $query;
    }
    
    /**
     * 
     * @param unknown $filepaths
     */
    public function getBuilderByFilepaths($filepaths)
    {
        is_array($filepaths) || $filepaths = [$filepaths];
        
        $query = $this->model->newQuery();
        foreach ($filepaths as $filepath) {
            $pathinfo = $this->explainPath($filepath);
            $query->orWhere([
                'filename' => $pathinfo['basename'],
                'dir' => $pathinfo['dirname'],
            ]);
        }
    
        return $query;
    }
    
    public function getBuilderByDirs($dirs)
    {
        is_array($dirs) || $dirs = [$dirs];
        
        $query = $this->model->newQuery();
        foreach ($dirs as $dir) {
            $query->orWhere([
                'dir' => $dir,
            ]);
        }
        
        return $query;
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
            $rand = rand(0,1000);
            $pathinfo['basename'] = $pathinfo['filename'].'-'.$rand.'.'.$pathinfo['extension'];
            $filename = $md5.'-'.$rand.'.'.$pathinfo['extension'];
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
        $this->disk->delete($this->getFilepaths($path));
        return $this->deletePaths($path);
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
        return $this->disk->lastModified($this->getFilepath($path));
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
        $filepaths = $this->disk->files($directory, $recursive);
        return $this->getPaths($filepaths);
    }

    /**
     * Get all of the files from the given directory (recursive).
     *
     * @param string|null $directory            
     * @return array
     */
    public function allFiles($directory = null)
    {
        $filepaths = $this->disk->allFiles($directory);
        return $this->getPaths($filepaths);
        
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
        $dirs = $this->disk->directories($directory, $recursive);
        return $this->getDirs($dirs);
    }

    /**
     * Get all (recursive) of the directories within a given directory.
     *
     * @param string|null $directory            
     * @return array
     */
    public function allDirectories($directory = null)
    {
        $dirs = $this->disk->allDirectories($directory);
        return $this->getDirs($dirs);
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
        $this->disk->deleteDirectory($directory);
        return $this->deletePathsByDirs($this->normalizeDirname($directory));
    }
    
    public function url($path)
    {
        return $this->disk->url($this->getFilepath($path));
    }
    
    public function applyPathPrefix($path)
    {
        return $this->disk->getAdapter()->applyPathPrefix($path);
    }
}