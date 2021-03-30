from matplotlib import pyplot as plt
import cv2
import pafy
import imutils
import time

from threading import Thread
from queue import Queue

from skimage.metrics import structural_similarity as ssim
from skimage.metrics import mean_squared_error

# environments

SKIP_FRAME = 90
DIFF_THRESH = 0.90

# helper function and classes

def optimizeFrame(frame):
    frame = imutils.resize(frame, width=320)
    frame = cv2.cvtColor(curr_frame, cv2.COLOR_BGR2GRAY)
    return frame

def displayFrame(frame):
    print()
    plt.imshow(frame, cmap=plt.cm.gray)
    plt.axis('off')
    plt.show()
    
class ThreadedStream: 
    def __init__(self, path, queueSize=4500, skip_frame=SKIP_FRAME):
        self.stream = cv2.VideoCapture(path)
        self.stopped = False
        self.queue = Queue(maxsize=queueSize)
        self.skip_frame = skip_frame
        self.counter = 0
        
    def start(self):
        thread = Thread(target=self.update, args=())
        thread.daemon = True
        thread.start()
        return self
    
    def update(self):
        while True:
            if self.stopped: 
                return
            if not self.queue.full():
                (grabbed, frame) = self.stream.read()
                self.counter += 1
                
                if not grabbed:
                    self.stop()
                    return

                # throw away skip frames
                if self.counter % self.skip_frame != 0:
                    continue

                self.queue.put(frame)
        
    def read(self):
        return self.queue.get()
    
    def hasMore(self):
        if self.stopped:
            return False
        else:
            while self.queue.empty():
                time.sleep(2)
            return True
    
    def getSize(self):
        return self.queue.qsize()
    
    def stop(self):
        self.stopped = True

# open video stream

# CSCI2100
# url = "https://youtu.be/MZ16A6X9pG4"
# UGEA2100
url = "https://www.youtube.com/watch?v=6FJOIxaD4z0"
video = pafy.new(url)
streams = video.streams
print(streams)

stream = streams[0]
capture = ThreadedStream(stream.url).start()

my_counter = 0
prev_frame = None

start_time = time.time()

while capture.hasMore():
    curr_frame = capture.read()
    my_counter += 1
    
    # first frame break
    if prev_frame is None:
        prev_frame = optimizeFrame(curr_frame)
        # displayFrame(prev_frame)
        continue
    
    # process
    start_processing = cv2.getTickCount() 
    curr_frame = optimizeFrame(curr_frame)
    mean_ssim = ssim(prev_frame, curr_frame)
    end_processing = cv2.getTickCount()
    processing_time = cv2.getTickFrequency() / (end_processing - start_processing);
    print("vid time: %d, frame %d, ssim %f, fps %f, queue size: %d       " % \
          (int(my_counter*SKIP_FRAME/30), my_counter*SKIP_FRAME, mean_ssim, processing_time, capture.getSize()), end='\r')
    
    if mean_ssim < DIFF_THRESH:
        # displayFrame(curr_frame)
        pass
        
    prev_frame = curr_frame
    
print()
print("Done")

end_time = time.time()
elapsed = end_time - start_time
print("Elapsed: %d seconds" % (elapsed))