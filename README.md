PHP AWS S3 - Change storage Class Util
===========================

Asynchronously and parallelly changes the storage class of all objects in an AWS S3 bucket.
Searches for objects in one class and converts them to another.

The rewriting of objects is done on the AWS side (invoicing an API PUT COPY command). The rewriting does not imply a period of unavailability of the object.

# Instruction

1. Copy config.template.php to config.php
2. Fill in config
3. Composer Install
4. ``` php Script.php ```

