from dotenv import dotenv_values

import boto3
from botocore.exceptions import NoCredentialsError

class S3Controller:
	def __init__(self): 
		self.config = dotenv_values(".env")
		self.access_key = self.config['AWS_ACCESS_KEY_ID']
		self.secret_key = self.config['AWS_SECRET_ACCESS_KEY']
		self.bucket = self.config['AWS_BUCKET']
		self.region = self.config['AWS_DEFAULT_REGION']

		self.s3 = boto3.client('s3', 
			aws_access_key_id=self.access_key,
			aws_secret_access_key=self.secret_key,
			region_name=self.region)

	def upload(self, key, body, content_type):
		try: 
			self.s3.put_object(
				Bucket=self.bucket,
				Key=key,
				Body=body,
				ContentType=content_type
			)
		except FileNotFoundError:
			print("the requested file is not found")
		except NoCredentialsError:
			print("Credentials error")
		
		