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
        $video_url_template = 'http://video.%s.nhl.com/videocenter/console';
        $teams = [
            // Western Conference
            'Anaheim Ducks' => [
                'subreddit' => 'anaheimducks',
                'nhl_code' => 'ducks'
            ],
            'Arizona Coyotes' => [
                'subreddit' => 'coyotes',
                'nhl_code' => 'coyotes',
            ],
            'Calgary Flames' => [
                'subreddit' => 'calgaryflames',
                'nhl_code' => 'flames'
            ],
            'Chicago Blackhawks' => [
                'subreddit' => 'hawks',
                'nhl_code' => 'blackhawks'
            ],
            'Colorado Avalanche' => [
                'subreddit' => 'coloradoavalanche',
                'nhl_code' => 'avalanche'
            ],
            'Dallas Stars' => [
                'subreddit' => 'dallasstars',
                'nhl_code' => 'stars'
            ],
            'Edmonton Oilers' => [
                'subreddit' => 'edmontonoilers',
                'nhl_code' => 'oilers'
            ],
            'Los Angeles Kings' => [
                'subreddit' => 'losangeleskings',
                'nhl_code' => 'kings'
            ],
            'Minnesota Wild' => [
                'subreddit' => 'wildhockey',
                'nhl_code' => 'wild'
            ],
            'Nashville Predators' => [
                'subreddit' => 'predators',
                'nhl_code' => 'predators'
            ],
            'San Jose Sharks' => [
                'subreddit' => 'sanjosesharks',
                'nhl_code' => 'sharks'
            ],
            'St. Louis Blues' => [
                'subreddit' => 'stlouisblues',
                'nhl_code' => 'blues'
            ],
            'Vancouver Canucks' => [
                'subreddit' => 'canucks',
                'nhl_code' => 'canucks'
            ],
            'Winnipeg Jets' => [
                'subreddit' => 'winnipegjets',
                'nhl_code' => 'jets'
            ],

            // Eastern Conference
            'Boston Bruins' => [
                'subreddit' => 'bostonbruins',
                'nhl_code' => 'bruins'
            ],
            'Buffalo Sabres' => [
                'subreddit' => 'sabres',
                'nhl_code' => 'sabres'
            ],
            'Carolina Hurricanes' => [
                'subreddit' => 'canes',
                'nhl_code' => 'hurricanes'
            ],
            'Columbus Blue Jackets' => [
                'subreddit' => 'bluejackets',
                'nhl_code' => 'bluejackets'
            ],
            'Detroit Red Wings' => [
                'subreddit' => 'detroitredwings',
                'nhl_code' => 'redwings'
            ],
            'Florida Panthers' => [
                'subreddit' => 'floridapanthers',
                'nhl_code' => 'panthers'
            ],
            'Montréal Canadiens' => [
                'subreddit' => 'habs',
                'nhl_code' => 'canadiens'
            ],
            'New Jersey Devils' => [
                'subreddit' => 'devils',
                'nhl_code' => 'devils'
            ],
            'New York Islanders' => [
                'subreddit' => 'newyorkislanders',
                'nhl_code' => 'islanders'
            ],
            'New York Rangers' => [
                'subreddit' => 'rangers',
                'nhl_code' => 'rangers'
            ],
            'Ottowa Senators' => [
                'subreddit' => 'ottawasenators',
                'nhl_code' => 'senators'
            ],
            'Philadelphia Flyers' => [
                'subreddit' => 'flyers',
                'nhl_code' => 'flyers'
            ],
            'Pittsburgh Penguins' => [
                'subreddit' => 'penguins',
                'nhl_code' => 'penguins'
            ],
            'Tampa Bay Lightning' => [
                'subreddit' => 'tampabaylightning',
                'nhl_code' => 'lightning'
            ],
            'Totonto Maple Leafs' => [
                'subreddit' => 'leafs',
                'nhl_code' => 'mapleleafs'
            ],
            'Washington Capitals' => [
                'subreddit' => 'caps',
                'nhl_code' => 'capitals'
            ]
        ];

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
            //'http://video.nhl.com/videocenter/console?id=819770'

            // schema 2
            //'http://video.nhl.com/videocenter/console?id=73534'
            'http://video.nhl.com/videocenter/console?id=209243'

            ##
            ## NOT SUPPORTED
            ##

            ##
            ## UNTESTED
            ##
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

            dd([
                'video' => $url,
                'title' => $video_name
            ]);
        }
    }
}
