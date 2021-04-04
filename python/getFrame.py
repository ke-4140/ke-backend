import base64
import sys

import pafy
import cv2

def getFrame(url, frame_no):
    
    url = "https://www.youtube.com/watch?v=Bqyw52tO7To"

    video = pafy.new(url)
    streams = video.streams
    
    stream = streams[0]
    capture = cv2.VideoCapture(stream.url)
    
    target_frame = frame_no

    capture.set(1, target_frame)
    ret, frame = capture.read()

    jpg = cv2.imencode('.jpg', frame)[1]
    jpg_base64 = base64.b64encode(jpg)

    return jpg_base64

if __name__ == '__main__':
	if len(sys.argv) != 3:
		print("Usage: getFrame.py src frame_no")
		exit(0)

	src = sys.argv[1]
	frame_no = int(sys.argv[2])

	result = getFrame(src, frame_no)
	print(result.decode())