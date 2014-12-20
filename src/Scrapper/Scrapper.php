<?php

namespace Scrapper;

class Scrapper
{
    protected $base_dir;

    public function __construct($base_dir = './')
    {
        $this->base_dir = $base_dir;
    }

    public function scrap($links)
    {
        foreach ($links as $link) {
            $base = $this->findBase($link);
            $dir  = $this->getPath($link);
            $path = $this->base_dir.$dir;

            if (!is_dir($path)) {
                echo 'Creating directory.. ('.$dir.')'."\n";
                mkdir($path);
            }

            $this->match($link, $path, $base);
        }
    }

    public function match($url, $path, $base)
    {
        $url = str_replace(' ', '%20', $url);
        $content = file_get_contents($url);

        if(preg_match_all('#<tr[^>]*>(.*?)</tr>#is', $content, $trs)) {

            $total = count($trs[1])-1;
            echo 'Found: ('.$total.')'."\n";

            foreach ($trs[1] as $serial => $tr) {

                //$pattern = '#td\>(.*)\<a href="(.*)">(.*)\<\/a\>#';
                $pattern = '#src="[^"](?:.*)file\=(.*)"(?:.*?)<a href="(.*)">(.*)<\/a>#is';

                if (preg_match_all($pattern, $tr, $matches)) {

                    foreach ($matches[3] as $key => $value) {

                        if (empty($value)) {
                            continue;
                        }

                        $dir = $path . $value;
                        $url = $base.$matches[2][$key];

                        if ($this->isDirectory($dir, $matches[1][$key])) {
                            if (!is_dir($dir)) {
                                echo 'Creating directory.. ('.$dir.')'."\n";
                                mkdir($dir);
                            }
                            $this->match($url, $dir.'/', $base);
                            continue;
                        }

                        echo '['.($serial).'/'.$total.'] '.$value;
                        ob_flush();
                        flush();
                        ob_end_clean();

                        $start = microtime(true);
                        $downloader = $this->downloader($url, $dir);
                        $size = $downloader->getTotalBytes();

                        if (empty($size)) {
                            echo " NOT FOUND"."\n";
                            continue;
                        }

                        if (file_exists($dir) && is_file($dir)) {
                            $filesize = filesize($dir);
                            $percentage = ($size/$filesize) * 100;

                            //echo ' S1:'.$size.'|'.$filesize;

                            if ($percentage >= 98) {
                                echo ' FOUND'."\n";
                                continue;
                            } else {
                                @unlink($dir);
                            }
                        }

                        echo " [".$this->formatBytes($size)."]";
                        echo " ...\n";
                        $downloader->download();

                        echo "\n";
                        echo '      DONE';
                        echo ' '.$this->took($start);
                        echo ' '.$this->formatBytes(filesize($dir));
                        echo "\n\n";
                    }
                }
            }

        }
    }

    public function took($start)
    {
        $end = microtime(true);

        return number_format((($end-$start)/60), 2). ' Min'; //value in seconds
    }

    public function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
         $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . '' . $units[$pow];
    }

    public function isDirectory($file, $icon = null)
    {   
        $ext = strtolower(strrchr($file, '.'));
        if ($ext && $ext == $icon) {
            return false;
        }

        $extensions = array(
            '.avi','.mkv','.rmvb','.flv','.wmv','.mpg','.mp4',
            '.mov','.mpg', '.mp2', '.mpeg', '.mpg', '.mpe', '.mpv',
            '.mpg', '.mpeg', '.m2v','.ogv', '.ogg','.divx','.rar',
            '.zip','.rm','.srt'
        );

        if (in_array($ext, $extensions)) {
            return false;
        }

        return true;
    }

    public function findBase($url)
    {
        $url = parse_url($url);
        $base  = 'http://';
        $base .= $url['host'];
        $base .= ($url['port']) ? ':'.$url['port'] : '';

        return $base;
    }

    public function getPath($url)
    {
        $url = parse_url($url);
        $query  = array_values(array_filter(explode('/', urldecode($url['path']))));

        return $query[count($query) - 1].'/';
    }

    public function downloader($url, $outputFile)
    {
        $url = strtok($url, '?');
        $url = str_replace(" ", "%20", $url);

        $downloader = new Downloader($url);
        $downloader->setOutputFile($outputFile);
        $downloader->setMinCallbackPeriod(5);
        $downloader->setMaxParallelChunks(50);
        $downloader->setChunkSize(1024 * 1024);

        $downloader->setProgressCallback(function ($position, $totalBytes) use ($downloader) {
            static $prevPosition = 0;
            static $prevTime = 0;

            $now = microtime(true);
            $speed = ($position - $prevPosition) / ($now - $prevTime);
            $speed = round($speed / 1024 / 1024, 2) . 'MB/s';
            $positionFormatted = round($position / 1024 / 1024, 2) . 'MB';
            $totalBytesFormatted = round($totalBytes / 1024 / 1024, 2) . 'MB';
            //$streams = $downloader->getRunningChunks();

            if ($now - $prevTime < 1e3) {
                echo "      SPEED: $speed; done: $positionFormatted / $totalBytesFormatted\n";
            }

            $prevPosition = $position;
            $prevTime = $now;

            return true;
        });

        return $downloader;
    }
}
