from dotenv import dotenv_values
import pymysql
import sys

class DatabaseUpdater:
	conn = None

	def __init__(self):

		# # already init connection early exit
		# if self.conn is not None:
		# 	print("reusing conn")
		# 	return
		
		# read db config from env file
		self.config = dotenv_values(".env")
		
		# try connection
		try:
			self.conn = pymysql.connect(
				host=self.config['DB_HOST'],
				port=int(self.config['DB_PORT']),
				db=self.config['DB_DATABASE'],
				user=self.config['DB_USERNAME'],
				passwd=self.config['DB_PASSWORD'],
				connect_timeout=5
			)
		except pymysql.MySQLError as e:
			print("DB connection error")
			sys.exit()

	def getJobID(self, uuid, src):
		self.conn.ping(reconnect=True)
		with self.conn:
			with self.conn.cursor() as cursor:

				query = """ select id from jobs
					where owner=%s and src=%s
				"""
				cursor.execute(query, (uuid, src))
				result = cursor.fetchone()
				return result[0]
				

	def updateJobState(self, uuid, src, status):
		self.conn.ping(reconnect=True)
		with self.conn:
			with self.conn.cursor() as cursor:

				query = """ update jobs
					set `status`=%s, `updated_at`=NOW()
					where owner=%s and src=%s
				"""
				affected_rows = cursor.execute(query, (status, uuid, src))
				
				if affected_rows < 1:
					print("Job not found")
					sys.exit()
			self.conn.commit()

	def insertOutput(self, job_id, vid_time, frame_no, ssim, img_addr):

		job_id = int(job_id)
		vid_time = int(vid_time)
		frame_no = int(frame_no)
		ssim = float(ssim)

		self.conn.ping(reconnect=True)
		with self.conn:
			with self.conn.cursor() as cursor:

				query = """ insert into outputs (
						job_id, vid_time, frame_no, ssim, img_addr, 
						read_at, created_at, updated_at
					) values (
						%s, %s, %s, %s, %s, NULL, NOW(), NOW()
					)
				"""
				affected_rows = cursor.execute(query, (job_id, vid_time, frame_no, ssim, img_addr))
				
				if affected_rows < 1:
					print("error inserting output in db")
			self.conn.commit()

			