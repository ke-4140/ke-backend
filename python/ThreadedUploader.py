import time

from threading import Thread
from queue import Queue
from DatabaseUpdater import DatabaseUpdater

class ThreadedUploader: 
    def __init__(self, queueSize=4500):
        self.stopped = False
        self.queue = Queue(maxsize=queueSize)
        self.db_updater = DatabaseUpdater()
        
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
            self.db_updater.insertOutput(
                attr['job_id'], 
                attr['vid_time'],
                attr['frame_no'],
                attr['ssim'],
                "image_path"
            )

    def put(self, frame, attr):
        self.queue.put((frame, attr))
    
    def stop(self):
        self.stopped = True