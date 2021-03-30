import time

import cv2
from threading import Thread
from queue import Queue

class ThreadedStream: 
    def __init__(self, path, queueSize=4500, skip_frame=0):
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