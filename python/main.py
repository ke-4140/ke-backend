import time
import sys

from matplotlib import pyplot as plt
import cv2
import pafy
import imutils
from skimage.metrics import structural_similarity as ssim
from skimage.metrics import mean_squared_error

from threading import Thread
from queue import Queue

from ThreadedStream import ThreadedStream
from ThreadedUploader import ThreadedUploader
from DatabaseController import DatabaseController

# environments

SKIP_FRAME = 90
DIFF_THRESH = 0.90

if len(sys.argv) != 3:
    print("Usage: main.py uuid src")
    exit(0)

uuid = sys.argv[1]
src = sys.argv[2]

# helper function and classes

def optimizeFrame(frame):
    frame = imutils.resize(frame, width=320)
    frame = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    return frame

def displayFrame(frame):
    print()
    plt.imshow(frame, cmap=plt.cm.gray)
    plt.axis('off')
    plt.show()


# open video stream

# CSCI2100
# url = "https://youtu.be/MZ16A6X9pG4"
# UGEA2100
# url = "https://www.youtube.com/watch?v=6FJOIxaD4z0"
url = src
video = pafy.new(url)
streams = video.streams
print(streams)

stream = streams[0]
capture = ThreadedStream(stream.url, skip_frame=SKIP_FRAME).start()
uploader = ThreadedUploader().start()
db_updater = DatabaseController()
db_updater.updateJobState(uuid, src, "running")
job_id = db_updater.getJobID(uuid, src)

my_counter = 0
solution_count = 0
prev_frame = None

start_time = time.time()

while capture.hasMore():
    raw_frame = capture.read()
    my_counter += 1
    
    # first frame break
    if prev_frame is None:
        prev_frame = optimizeFrame(raw_frame)
        # displayFrame(prev_frame)
        continue
    
    # process
    start_processing = cv2.getTickCount() 
    curr_frame = optimizeFrame(raw_frame)
    mean_ssim = ssim(prev_frame, curr_frame)
    end_processing = cv2.getTickCount()
    processing_time = cv2.getTickFrequency() / (end_processing - start_processing);

    # stat
    vid_time = int(my_counter*SKIP_FRAME/30)
    frame_no = my_counter*SKIP_FRAME
    queue_size = capture.getSize()
    fps = processing_time
    print("vid time: %d, frame %d, ssim %f, fps %f, queue size: %d       " % \
          (vid_time, frame_no, mean_ssim, fps, queue_size), end='\r')
    
    if mean_ssim < DIFF_THRESH:
        # displayFrame(curr_frame)
        attr = {
            "job_id": job_id,
            "vid_time": vid_time,
            "frame_no": frame_no,
            "ssim": mean_ssim,
            "queue_size": queue_size,
            "fps": fps,
        }
        uploader.put(raw_frame, attr)
        solution_count += 1
        print(solution_count)
        pass
        
    prev_frame = curr_frame
    
print()
print("Done")
db_updater.updateJobState(uuid, src, "finished")

end_time = time.time()
elapsed = end_time - start_time
print("Elapsed: %d seconds" % (elapsed))