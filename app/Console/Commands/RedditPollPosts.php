<?php

namespace App\Console\Commands;

use App\Jobs;
use App\Post;
use Guzzle;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Log;

class RedditPollPosts extends Command
{
    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reddit:posts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll Reddit for new posts.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $search_domains = [
            //config('nhl.urls.video.base')
        ];

        $teams = config('nhl.teams');
        foreach ($teams as $team) {
            //$search_domains[] = sprintf(config('nhl.urls.video.team'), $team['nhl_code']);
        }

        $search_domains[] = sprintf(config('nhl.urls.video.team'), 'sharks');

        foreach ($search_domains as $domain) {
            $response = Guzzle::get('https://www.reddit.com/domain/' . $domain . '/new.json?limit=100', [
                'headers' => [
                    'User-Agent' => config('services.reddit.user_agent')
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                Log::info('Having trouble pulling new posts from Reddit.', [
                    'domain' => $domain,
                    'status_code' => $response->getStatusCode()
                ]);

                // Reddit might be down, so sleep for a bit.
                sleep(30);
                continue;
            }

            $json = json_decode($response->getBody(), true);
            foreach ($json['data']['children'] as $listing) {
                // "t3" means "link". https://www.reddit.com/dev/api
                if ($listing['kind'] != 't3') {
                    continue;
                }

                $data = $listing['data'];
                $url = htmlspecialchars_decode($data['url']);

                if ($data['author'] !== 'lorderunion') {
                    continue;
                }

                // Verify that this URL is valid, and has a video ID in the query string.
                parse_str(parse_url($url)['query'], $query);
                if (!isset($query['id']) || empty($query['id']) || (int)$query['id'] <= 0) {
                    continue;
                }

                $video_id = $query['id'];
                $thing_id = $data['id'];

                // Has this thing already been processed?
                if (Post::where(['thing_id' => $thing_id])->first() !== null) {
                    continue;
                }

                Post::create([
                    'thing_id' => $thing_id,
                    'subreddit' => $data['subreddit'],
                    'url' => $data['permalink']
                ]);

                $this->info($data['permalink']);
                $this->comment(' - ' . $url);

                $this->dispatch(new Jobs\ProcessVideo('post', $thing_id, $video_id, $url));
            }
        }
    }
}
