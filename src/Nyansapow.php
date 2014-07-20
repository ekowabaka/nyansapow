<?php
namespace nyansapow;

/**
 * The Nyansapow class which represents a nyansapow site. This class performs
 * the task of converting the input files into the output site. 
 */
class Nyansapow
{
    private $options;
    private $source;
    private $destination;
    private $pages = array();
    private $pageFiles = array();
    private $home;
    
    private function __construct($source, $destination, $options)
    {
        $this->home = dirname(__DIR__);
        if($source == '')
        {
            $source = getcwd();
        }
        
        if(!file_exists($source) && !is_dir($source)) 
        {
            throw new Exception("Input directory `{$source}` does not exist or is not a directory.");
        }
        
        if($destination == '')
        {
            $destination = getcwd() . "/_site";
        }        
        
        $pageDetails = $this->getPageFiles($source);
        $this->pageFiles = $pageDetails['files'];
        $this->pages = $pageDetails['pages'];
        
        $this->source = $source;
        $this->options = $options;
        $this->destination = $destination;
    }
    
    
    public function getDestination()
    {
        return $this->destination;
    }
    
    public function getHome()
    {
        return $this->home;
    }
    
    //public function 
    
    /**
     * 
     * @param type $source
     * @return type
     */
    private function getPageFiles($source)
    {
        $dir = dir($source);
        $pages = array();
        $pageFiles = array();
        $directories = array();
        $settingsFile = false;

        if(file_exists($source . "/site.ini"))
        {
            $settingsFile = $source . '/site.ini';
            $pages[] = '__site_settings__';
            $pageFiles[] = $settingsFile;
        }
            
        while (false !== ($entry = $dir->read())) 
        {
            $file = "$source/$entry";
            if(preg_match("/(?<page>.*)(\.)(?<extension>\md|\textile)/i", $entry, $matches) && !is_dir($file))
            {
                $pages[] = $matches['page'];
                $pageFiles[] = $file;
            }
            else if(is_dir($file) && ($entry != '.' && $entry != '..'))
            {
                $directories[] = $file;
            }
        }
        
        foreach($directories as $directory)
        {
            $pageDetails = $this->getPageFiles($directory);
            $pages = array_merge($pages, $pageDetails['pages']);
            $pageFiles = array_merge($pageFiles, $pageDetails['files']);
            if($settingsFile !== false)
            {
                $pages[] = '__site_settings__';
                $pageFiles[] = $settingsFile;                
            }
        }
        
        return array(
            'files' => $pageFiles,
            'pages' => $pages
        );
    }

    public static function open($source, $destination, $options = array())
    {
        return new Nyansapow($source, $destination, $options);
    }
    
    public function writeAssets()
    {
        self::mkdir($this->destination);
        self::copyDir("$this->home/themes/default/assets", "{$this->destination}");        
    }
    
    public function write($files = array())
    {
        if(count($files) == 0)
        {
            $this->writeAssets();
            if(is_dir("{$this->source}/images"))
            {
                self::copyDir("{$this->source}/images", "{$this->destination}");
            }
            $files = $this->pageFiles;
        }
        
        $processor = SiteProcessor::init($this);
        
        foreach($files as $path)
        {
            $dir = substr(dirname($path), strlen($this->source) + 1);
            $file = basename($path);
            if($dir != '') $dir .= '/';
            
            // Switch the processor when the site.ini file has changed
            if($file == 'site.ini')
            {
                $processor->outputSite();
                $settings = parse_ini_file($path);
                $processor = SiteProcessor::get($settings);
            }
            else
            {
                $processor->addFile($dir . $file);
            }
        }
        
        $processor->outputSite();
        
        /*$filesWritten = array();
        
        $m = new \Mustache_Engine();   
        $this->currentDocument = new \DOMDocument();
        
        foreach($files as $path)
        {
            $file = basename($path);
            $dir = substr(dirname($path), strlen(getcwd()) + 1);
            $this->assetsLocation = '';
            
            if($dir != '')
            {
                $dir .= '/';
                $this->assetsLocation = str_repeat('../', substr_count($dir, '/'));
            }
            
            self::mkdir($dir);
            
            if(preg_match("/(?<page>.*)(\.)(?<extension>\md|\textile)/i", $file, $matches))
            {
                switch($matches['page'])
                {
                    case 'Home':
                        $output = "index.html";
                        break;

                    default:
                        $output = "{$matches['page']}.html";
                        break;
                }                
            }
            else if(preg_match("/(?<dir>assets|images)(\/)(.*)(\.*)/", $file, $matches))
            {
                if(!is_dir($matches['dir']))
                {
                    self::mkdir("{$this->destination}/{$matches['dir']}");
                }
                copy($path, "{$this->destination}/{$file}");
                continue;
            }
            else if($file == 'site.ini')
            {
                
                continue;
            }
            else
            {
                // Do nothing
                continue;
            }
            

            $input = file_get_contents("{$this->source}/$file");
            $outputFile = $this->destination . ($dir =='' ? '' : "/$dir") . "/$output";
                        
            $preParsed = Parser::preParse($input);
            
            \Michelf\MarkdownExtra::setCallbacks(new Callbacks());
            $markedup = \Michelf\MarkdownExtra::defaultTransform($preParsed);
            $layout = file_get_contents("$this->home/themes/default/templates/layout.mustache");
            
            @$this->currentDocument->loadHTML($markedup);
            $h1s = $this->currentDocument->getElementsByTagName('h1');
            
            Parser::setNyansapow($this);
            Parser::domCreated($this->currentDocument);
            
            $body = $this->currentDocument->getElementsByTagName('body');
            $content = Parser::postParse(
                str_replace(array('<body>', '</body>'), '', $this->currentDocument->saveHTML($body->item(0)))
            );
            
            $webPage = $m->render(
                $layout, 
                array(
                    'body' => $content,
                    'page_title' => $h1s->item(0)->nodeValue,
                    'site_name' => $this->options['site-name'],
                    'date' => date('jS F, Y H:i:s'),
                    'assets_location' => $this->assetsLocation
                )
            );

            self::writeFile($outputFile, $webPage);
            $filesWritten[] = $output;
        }*/
    }

    public static function copyDir($source, $destination)
    {
        foreach(glob($source) as $file)
        {
            $newFile = (is_dir($destination) ?  "$destination/" : ''). basename("$file");

            if(is_dir($file))
            {
                self::mkdir($newFile);
                self::copyDir("$file/*", $newFile);
            }
            else
            {
                copy($file, $newFile);
            }
        }
    }

    public static function mkdir($path)
    {
        if($path == '') return false;
        if(!file_exists(dirname($path)))
        {
            self::mkdir(dirname($path));
        }
        else if(!is_writable(dirname($path)))
        {
            throw new Exception("You do not have permissions to create the $path directory.");
        }
        else if(is_dir($path))
        {
            // Skip
        }
        else
        {
            mkdir($path);
        }
        return $path;
    }    
    
    public function getPages()
    {
        return $this->pages;
    }
}

class Exception extends \Exception
{
    
}

