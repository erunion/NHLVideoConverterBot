var url = require('url');
var querystring = require('querystring');
var cheerio = require('cheerio');
var request = require('request');

//authentication-less url
//http://nhl.cdnllnwnl.neulion.net/u/nhl/vod/flv/2015/10/01/839336_1_83_van_edm_1516_h_quickpick12_1_1600K_16x9_sd.mp4


http://sharks.cdnllnwnl.neulion.net/u/sharks/vod/flv/2015/09/26/837502_CampQuotesMueller-SD_sd.mp4
http://sharks.cdnllnwnl.neulion.net/s/sharks/vod/flv/2015/09/26/837502_CampQuotesMueller-SD.mp4?eid=836893&pid=837502&gid=3025&pt=1?eid=836893&pid=837502&gid=3025&pt=1

http://sharks.cdnllnwnl.neulion.net/u/sharks/vod/flv/2015/09/30/838796_CampQuotesTennyson-SD_sd.mp4
http://sharks.cdnllnwnl.neulion.net/s/sharks/vod/flv/2015/09/30/838796_CampQuotesTennyson-SD.mp4?eid=838187&pid=838796&gid=3025&pt=1?eid=838187&pid=838796&gid=3025&pt=1

http://canadiens.cdnllnwnl.neulion.net/u/canadiens/vod/flv/2015/10/01/839017_20151001_PriceTrippingTinordi_sd.mp4
http://canadiens.cdnllnwnl.neulion.net/s/canadiens/vod/flv/2015/10/01/839017_20151001_PriceTrippingTinordi.mp4?eid=838408&pid=839017&gid=3016&pt=1?eid=838408&pid=839017&gid=3016&pt=1

http://oilers.cdnllnwnl.neulion.net/u/oilers/vod/flv/2015/07/03/831709_20150703_cooking_sd.mp4
http://oilers.cdnllnwnl.neulion.net/s/oilers/vod/flv/2015/07/03/831709_20150703_cooking.mp4?eid=831100&pid=831709&gid=3012&pt=1?eid=831100&pid=831709&gid=3012&pt=1

http://oilers.cdnllnwnl.neulion.net/u/oilers/vod/flv/2015/09/25/836867_20150925_mclellan_sd.mp4
http://oilers.cdnllnwnl.neulion.net/s/oilers/vod/flv/2015/09/25/836867_20150925_mclellan.mp4?eid=836258&pid=836867&gid=3012&pt=1?eid=836258&pid=836867&gid=3012&pt=1

http://oilers.cdnllnwnl.neulion.net/u/oilers/vod/flv/2015/09/29/838416_20150929_landergoal2_sd.mp4
http://oilers.cdnllnwnl.neulion.net/s/oilers/vod/flv/2015/09/29/838416_20150929_landergoal2.mp4?eid=837807&pid=838416&gid=3012&pt=1?eid=837807&pid=838416&gid=3012&pt=1

http://sharks.cdnllnwnl.neulion.net/u/sharks/vod/flv/2015/09/30/838628_20150929_Burns_Goal_sd.mp4
http://sharks.cdnllnwnl.neulion.net/s/sharks/vod/flv/2015/09/30/838628_20150929_Burns_Goal.mp4?eid=838019&pid=838628&gid=3025&pt=1?eid=838019&pid=838628&gid=3025&pt=1"

http://nhl.cdnllnwnl.neulion.net/u/nhlmobile/vod/nhl/2013/03/14/401/pc/2_401_lak_sjs_1213_h_quickpick1_1_1600K_16x9_sd.mp4
http://nhl.cdnllnwnl.neulion.net/u/nhlmobile/vod/nhl/2013/03/14/401/pc/2_401_lak_sjs_1213_h_quickpick1_1_1600K_16x9.mp4?eid=220822&pid=220991&gid=3025&pt=1

/*
    _console.playVideo("fvod","838416","http://oilers.cdnllnwnl.neulion.net/s/oilers/vod/flv/2015/09/29/838416_20150929_landergoal2.mp4?eid=837807&pid=838416&gid=3012&pt=1?eid=837807&pid=838416&gid=3012&pt=1","OIL GAUGE  Anton Lander makes it 2-0.","1",false,null,true,"4","0","");
*/


var raw_video_url = 'http://video.sharks.nhl.com/videocenter/console?id=837502';
console.log('raw video url=', raw_video_url);

request(raw_video_url, function (error, response, body) {
    if (error) {
        throw error;
    }

    //body = JSON.parse(body);
    //console.log(body);

    var video = body.match(/_console.playVideo\((.)*\);/);
    console.log(video);

});


/*var parsed = url.parse(raw_video_url);
var video_id = querystring.parse(parsed.query).id;
var embed_url = 'https://video.nhl.com/videocenter/embed?playlist=' + video_id + '&playerType=s&twitter=true';
console.log('embed url=', embed_url);

var playlist_url = 'http://video.nhl.com/videocenter/servlets/playlist?ids=' + video_id + '&format=json';
console.log('playlist url=', playlist_url);*/

/*request(playlist_url, function (error, response, body) {
    if (error) {
        throw error;
    }

    body = JSON.parse(body);
    var video_data = body[0];

    console.log('video_data=', video_data);
});*/

//request.get(playlist_url, function);

//VideoCenter.nlLoadScript(NL_LOC_SERVER+"servlets/playlist?"+data.join("&"));


//http://sharks.cdnllnwnl.neulion.net/s/sharks/vod/flv/2015/09/26/837320_20150925_Donskoi_Goal.mp4?eid=836711&pid=837320&gid=3025&pt=1




/*
<script>var NL_VERSION="20150113";</script>
<script>var g_secure=true;</script>

var g_twitter=true;
function listenUnsupported() {
    alert("This video can not be played on your device.");
}

var settings = {
    instanceId:"1",
    containerId:"playerContainer",
    unsupportedCallback:listenUnsupported,
    section:"embedsocial",
    playerType:"s",
    videoChannel:"social"
}

VideoCenter.setup(settings);
var video = {
    playlist:"837320".split(",")
}

VideoCenter.play(video,"1");
*/

