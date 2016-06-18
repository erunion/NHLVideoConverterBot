<?php

namespace App\Console\Commands;

use Exception;
use Guzzle;
use Illuminate\Console\Command;

class NHLVideo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nhl:video';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $videos = [
            ##
            ## SUPPORTED
            ##
            // schema 1
            //'http://video.sharks.nhl.com/videocenter/console?id=837502'
            //'http://video.sharks.nhl.com/videocenter/console?id=560769&catid=935'
            //'http://video.sharks.nhl.com/videocenter/console?id=837320'
            //'http://video.canadiens.nhl.com/videocenter/console?id=839017'
            //'http://video.oilers.nhl.com/videocenter/console?id=840468&catid=4'
            //'http://video.kings.nhl.com/videocenter/console?id=840648'
            //'http://video.hurricanes.nhl.com/videocenter/console?id=840607&catid=684'
            //'http://video.nhl.com/videocenter/console?id=819770',
            //'http://video.nhl.com/videocenter/console?id=830129&catid=345',

            // schema 2
            //'http://video.nhl.com/videocenter/console?id=73534'
            //'http://video.nhl.com/videocenter/console?id=209243'

            ##
            ## NOT SUPPORTED
            ##

            ##
            ## UNTESTED
            ##

            'http://video.nhl.com/videocenter/console?catid=1528&id=840482&lang=en'
        ];

        $video_url = current($videos);

        try {
            // NHL video subdomains do not support HTTPS.
            if (substr($video_url, 0, 5) === 'https') {
                $video_url = substr_replace($video_url, 'http', 0, 5);
            }

            $response = Guzzle::get($video_url);
            if ($response->getStatusCode() !== 200) {
                throw new Exception('Encountered trouble talking to the NHL.');
            }
        } catch (Exception $e) {
            dd($e->getMessage());
        }

        ##
        ## Grab the video permalink.
        ###
        preg_match_all("/_console.playVideo\((.*)\);/ui", $response->getBody(), $matches);
        if (empty($matches[0])) {
            throw new Exception('Failed to parse ' . $video_url . ' for videos.');
        }

        foreach ($matches[1] as $match) {
            // After taking a bit of time to do some digging, I'd bet it's safe to assume that no NHL video has a comma
            // in its title, so it's safe to explode on that. If one does crop up, though, this explode will need to
            // be completely rewritten since boolean and null arguments in _console.playVideo() aren't contained in
            // quotation marks.
            $match = explode(',', $match);

            // Arguments for _console.playVideo() were found here:
            // http://neulionms-a.akamaihd.net/nhlvc/player/videocenter/scripts/neulion/console.js?v=20150113
            $video_id = $match[1];
            $url = preg_replace('/\?.*/', '', $match[2]);
            $video_name = $match[3];

            $schemas = [
                'http:\/\/(.*).(.*).neulion.net\/s\/(.*)\/vod\/flv\/([0-9]{4})\/([0-9]{2})\/([0-9]{2})\/(.*).mp4',
                'http:\/\/nhl.(.*).neulion.net\/s\/nhl\/vod\/flv\/(.*).mp4'
            ];

            // IN: http://sharks.cdnllnwnl.neulion.net/s/sharks/vod/flv/2015/09/26/837502_CampQuotesMueller-SD.mp4
            // OUT: http://sharks.cdnllnwnl.neulion.net/u/sharks/vod/flv/2015/09/26/837502_CampQuotesMueller-SD_sd.mp4
            if (preg_match('/' . $schemas[0] . '/ui', $url, $matches)) {
                $url = $matches[0];
                $url = preg_replace('/neulion.net\/s\/(.*)\/vod/ui', 'neulion.net/u/$1/vod', $url);
                $url = substr_replace($url, '_sd' . substr($url, -4), -4);

            // IN: http://nhl.cdnllnwnl.neulion.net/s/nhl/vod/flv/209243_Lemieux_Cancer_Feature.mp4
            // OUT: http://nhl.cdnllnwnl.neulion.net/u/nhl/vod/flv/209243_Lemieux_Cancer_Feature_sd.mp4
            } elseif (preg_match('/' . $schemas[1] . '/ui', $url, $matches)) {
                $url = $matches[0];
                $url = str_replace('neulion.net/s/nhl/vod', 'neulion.net/u/nhl/vod', $url);
                $url = substr_replace($url, '_sd' . substr($url, -4), -4);
            } else {
                throw new Exception($url . ' is not yet supported.');
            }

            $url_response = Guzzle::head($url);
            $content_type = $url_response->getHeader('Content-Type')[0];
            $content_length = $url_response->getHeader('Content-Length')[0];
            if ($url_response->getStatusCode() !== 200) {
                throw new Exception($url . ' has some issues loading.');
            } elseif ($content_type !== 'video/mp4') {
                throw new Exception($url . ' is a ' . $content_type);
            }

            dd([
                'video' => $url,
                'title' => $video_name,
                'filesize' => $this->formatSizeUnits($content_length)
            ]);
        }
    }

    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}
