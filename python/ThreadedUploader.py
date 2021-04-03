import time
import datetime

from threading import Thread
from queue import Queue
from DatabaseController import DatabaseController
from S3Controller import S3Controller

import cv2

class ThreadedUploader: 
    def __init__(self, queueSize=4500):
        self.stopped = False
        self.queue = Queue(maxsize=queueSize)
        self.db_updater = DatabaseController()
        self.s3 = S3Controller()
        
    def start(self):
        thread = Thread(target=self.work, args=())
        thread.daemon = True
        thread.start()
        return self
    
    def work(self):
        while True:
            if self.stopped: 
                return

            if self.queue.empty():
                time.sleep(2)
                continue

            # upload stuff
            (frame, attr) = self.queue.get()
            now = datetime.datetime.now().strftime("%Y%m%dT%H%M%S")
            frame_name = "{}_{}_{}.jpg".format(attr['job_id'], attr['frame_no'], now)
            self.db_updater.insertOutput(
                attr['job_id'], 
                attr['vid_time'],
                attr['frame_no'],
                attr['ssim'],
                frame_name
            )
            jpg_byte = cv2.imencode('.jpg', frame)[1].tostring()
            self.s3.upload(frame_name, jpg_byte, 'image/jpg')

    def put(self, frame, attr):
        self.queue.put((frame, attr))
    
    def stop(self):
        self.stopped = True