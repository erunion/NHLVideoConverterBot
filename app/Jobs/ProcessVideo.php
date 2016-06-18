<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Video;
use Exception;
use Guzzle;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessVideo extends Job implements SelfHandling, ShouldQueue
{
    protected $thing_id;
    protected $id;
    protected $url;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($thing_type, $thing_id, $id, $url)
    {
        $this->thing_type = $thing_type;
        $this->thing_id = $thing_id;
        $this->id = (int) $id;
        $this->url = $url;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Has this video already been processed?
        $video = Video::where(['id' => $this->id])->first();
        if ($video) {
            echo 'already processed!' . "\n";
            return;
        }

        try {
            // NHL video subdomains do not support HTTPS.
            if (substr($this->url, 0, 5) === 'https') {
                $this->url = substr_replace($this->url, 'http', 0, 5);
            }

            $response = Guzzle::get($this->url);
            if ($response->getStatusCode() !== 200) {
                throw new Exception('Encountered trouble talking to the NHL.');
            }
        } catch (Exception $e) {
            dd($e->getMessage());
        }

        ##
        ## Grab the video permalink.
        ##
        preg_match_all("/_console.playVideo\((.*)\);/ui", $response->getBody(), $matches);
        if (empty($matches[0])) {
            echo 'Failed to parse ' . $this->url . "\n";
            return;
        }

        foreach ($matches[1] as $match) {
            // Since the contents of _console.playVideo() are wrapped within quotation marks, save for booleans, we need
            // to explode on ",", so we can easily pluck out the URL and video title. We aren't splitting on commas
            // alone, because video titles often have commas within them, and splitting on those cut them off.
            $match = explode('","', $match);

            // Arguments for _console.playVideo() were found here:
            // http://neulionms-a.akamaihd.net/nhlvc/player/videocenter/scripts/neulion/console.js?v=20150113
            $url = preg_replace('/\?.*/', '', $match[2]);

            // Strip out any prefix and suffix quotation mark from the playVideo() code.
            $title = $match[3];
            if (substr($title, 0, 1) === '"') {
                $title = substr($title, 1, -1);
            }

            if (substr($title, -1, 0) === '"') {
                $title = substr($title, 0, -2);
            }

            $schemas = [
                'http:\/\/(.*).(.*).neulion.net\/s\/(.*)\/vod\/flv\/([0-9]{4})\/([0-9]{2})\/([0-9]{2})\/(.*).mp4',
                'http:\/\/nhl.(.*).neulion.net\/s\/nhl\/vod\/flv\/(.*).mp4',
                'http:\/\/(.*).(.*).neulion.net\/u\/nhlmobile\/vod\/(.*)\/([0-9]{4})\/([0-9]{2})\/([0-9]{2})\/(.*).mp4',
                'http:\/\/(.*).(.*).neulion.com\/nhl\/vod\/([0-9]{4})\/([0-9]{2})\/([0-9]{2})\/(.*).mp4'
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

            // IN: http://nhl.cdnllnwnl.neulion.net/u/nhlmobile/vod/xm/2015/09/24/20150924/pc/2_20150924_xm1_xm_2010_h_quickpick7_1_1600K_16x9.mp4
            // OUT: <no change>
            } elseif (preg_match('/' . $schemas[2] . '/ui', $url, $matches)) {
                $url = $matches[0];

            // IN: http://e1.cdnak.neulion.com/nhl/vod/2015/03/26/1107/2_1107_nyr_ott_1415_h_discrete_ott479_goal_1_1600.mp4
            // OUT: <no change>
            } elseif (preg_match('/' . $schemas[3] . '/ui', $url, $matches)) {
                $url = $matches[0];

            } else {
                echo $url . ' is not supported.' . "\n";
                return;
            }

            $url_response = Guzzle::head($url);
            $content_type = $url_response->getHeader('Content-Type')[0];
            $content_length = $url_response->getHeader('Content-Length')[0];
            if ($url_response->getStatusCode() !== 200) {
                throw new Exception($url . ' has some issues loading.');
            } elseif ($content_type !== 'video/mp4') {
                throw new Exception($url . ' is a ' . $content_type);
            }

            $filesize = $this->formatSizeUnits($content_length);

            Video::create([
                'id' => $this->id,
                'thing_id' => $this->thing_id,
                'url' => $this->url,
                'title' => $title,
                'converted_url' => $url,
                'filesize' => $filesize
            ]);

            $comment = <<<COMMENT
[Video: %s](%s) (%s)
COMMENT;

            $comment = sprintf($comment, $title, $url, $filesize);
            \Log::info($comment);

        }
    }

    /**
     * Take a byte string and convert it into a readable filesize representation.
     * @param  string $bytes
     * @return string
     */
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
