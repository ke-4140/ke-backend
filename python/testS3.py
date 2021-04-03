from S3Controller import S3Controller

s3 = S3Controller()

s3.upload("test", b"12345", "text/plain")