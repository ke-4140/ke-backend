from youtube_transcript_api import YouTubeTranscriptApi
from urllib.parse import parse_qs, urlparse
import re
import sys
import json


# from pafy github
def extract_video_id(url):
    """ Extract the video id from a url, return video id as str. """
    idregx = re.compile(r'[\w-]{11}$')
    url = str(url).strip()

    if idregx.match(url):
        return url # ID of video

    if '://' not in url:
        url = '//' + url
    parsedurl = urlparse(url)
    if parsedurl.netloc in ('youtube.com', 'www.youtube.com', 'm.youtube.com', 'gaming.youtube.com'):
        query = parse_qs(parsedurl.query)
        if 'v' in query and idregx.match(query['v'][0]):
            return query['v'][0]
    elif parsedurl.netloc in ('youtu.be', 'www.youtu.be'):
        vidid = parsedurl.path.split('/')[-1] if parsedurl.path else ''
        if idregx.match(vidid):
            return vidid

    err = "Need 11 character video id or the URL of the video. Got %s"
    raise ValueError(err % url)


def getTranscript(url):
	# parse url
	video_id = extract_video_id(url)
	# get transcript
	result_array = YouTubeTranscriptApi.get_transcript(video_id)
	return json.dumps(result_array)


if __name__ == '__main__':
	if len(sys.argv) != 2:
		print("Usage: getTranscript.py url")
		exit(0)

	url = sys.argv[1]
	result = getTranscript(url)
	print(result)