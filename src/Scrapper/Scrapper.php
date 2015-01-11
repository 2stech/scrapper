<?php

namespace Scrapper;

class Scrapper
{
    protected $base_dir;

    protected $options;

    public function __construct($base_dir = './', $options = [])
    {
        $this->base_dir = $base_dir;
        $this->options = $options;
    }

    public function scrap($links)
    {
        foreach ($links as $link) {
            $directory = '';

            if (is_array($link)) {
                $directory = $link['directory'].DIRECTORY_SEPARATOR;
                $link = $link['link'];
            }

            if (!is_array($link)) {
                $link = [$link];
            }

            foreach ($link as $lin) {

                $base = $this->findBase($lin);
                $dir  = $this->getPath($lin);
                $path = $this->base_dir.$directory.$dir;
                $path = str_replace(['/','\\'], DIRECTORY_SEPARATOR, $path);

                if (!is_dir($path)) {
                    echo 'Creating directory... ('.$path.')'."\n";
                    mkdir($this->cleanDir($path), 0777, true);
                }

                $this->match($lin, $path, $base);
            }
        }
    }

    public function match($url, $path, $base)
    {
        $url = str_replace(' ', '%20', $url);

        // Is file or directoy
        if (!$this->isDirectory($url)) {

            $title = $this->getBaseName($url);
            $dir = $path . $title;

            echo '[1/1] ';
            $this->download($url, $dir, $title);
            return true;
        }

        $content = file_get_contents($url);

        if (preg_match_all('#<tr[^>]*>(.*?)</tr>#is', $content, $trs)) {

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
                                mkdir($this->cleanDir($dir), 0777, true);
                            }
                            $this->match($url, $dir.'/', $base);
                            continue;
                        }

                        echo '['.($serial).'/'.$total.'] ';

                        $this->download($url, $dir, $value);
                    }
                }
            }

        }
    }

    protected function download($url, $dir, $title = null)
    {
        echo $title;
        ob_flush();
        flush();
        ob_end_clean();

        $start = microtime(true);
        $downloader = $this->downloader($url, $dir);
        $size = $downloader->getTotalBytes();

        if (empty($size)) {
            echo " NOT FOUND"."\n";
            return true;
        }

        if (file_exists($dir) && is_file($dir)) {
            $filesize = filesize($dir);
            $percentage = round(($filesize/$size) * 100, 2);

            echo ' SIZE ['.$this->formatBytes($filesize).'/'.$this->formatBytes($size).'] '.$percentage.'%'."\n";

            if ($percentage >= 98) {
                echo ' FOUND & MATCH'."\n";
                return true;
            } else {
                echo ' MIS MATCH';
                @unlink($dir);
            }
            echo "\n";
        }

        echo " [".$this->formatBytes($size)."]";
        echo " ...\n";
        $downloader->download();

        $filesize = filesize($dir);
        echo "\n";
        echo '      DONE';
        echo ' '.$this->took($start);
        echo ' ['.$this->formatBytes($filesize).'/'.$this->formatBytes($size).']';
        echo "\n\n";
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
        return number_format($bytes, $precision, '.', '') . ' ' . $units[$pow];
    }

    protected function cleanDir($dir)
    {
        return $dir;
    }

    public function isDirectory($file, $icon = null)
    {
        $ext = strtolower(strrchr($file, '.'));
        if ($ext && $ext == strtolower($icon)) {
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

    public function getBaseName($url)
    {
        $url    = parse_url($url);
        $query  = array_values(array_filter(explode('/', urldecode($url['path']))));

        return $query[count($query) - 1];
    }


    protected function secondsToWords($seconds)
    {
        $ret = "";

        /*** get the days ***/
        $days = intval(intval($seconds) / (3600*24));
        if($days> 0)
        {
            $ret .= " ".$days." days";
        }

        /*** get the hours ***/
        $hours = (intval($seconds) / 3600) % 24;
        if($hours > 0)
        {
            $ret .= " ".$hours." hours";
        }

        /*** get the minutes ***/
        $minutes = (intval($seconds) / 60) % 60;
        if($minutes > 0)
        {
            $ret .= " ".$minutes." mins";
        }

        /*** get the seconds ***/
        $seconds = intval($seconds) % 60;
        if ($seconds > 0 && $minutes <= 0) {
            $ret .= " ".$seconds." secs";
        }

        return $ret;
    }

    public function downloader($url, $outputFile)
    {
        $url = strtok($url, '?');
        $url = str_replace(" ", "%20", $url);

        $downloader = new Downloader($url);
        $downloader->setOutputFile($outputFile);
        $downloader->setMinCallbackPeriod(10);
        $downloader->setMaxParallelChunks(25);
        //$downloader->setMinCallbackPeriod(10);

        $downloader->setProgressCallback(function ($position, $totalBytes) use ($downloader) {
            static $prevPosition = 0;
            static $prevTime = 0;

            $now = microtime(true);
            $speed = ($position - $prevPosition) / ($now - $prevTime);

            //RemainingInSeconds = (TotalSize * TransferSpeed) - DownloadedSoFar
            //RemainingInSeconds = (TotalSize - DownloadedSoFar) / TransferSpeed
            $time = ($totalBytes - $position) / $speed;

            $streams = $downloader->getRunningChunks();

            $speed = $this->formatBytes($speed).'/s';
            $positionFormatted = $this->formatBytes($position);
            $totalBytesFormatted = $this->formatBytes($totalBytes);
            $time = $this->secondsToWords($time);

            if ($now - $prevTime < 1e3) {
                echo "  ".$speed.' - '.$positionFormatted.' of '.$totalBytesFormatted.', '.trim($time).' left'."\n";
            }

            $prevPosition = $position;
            $prevTime = $now;

            return true;
        });

        return $downloader;
    }
}
