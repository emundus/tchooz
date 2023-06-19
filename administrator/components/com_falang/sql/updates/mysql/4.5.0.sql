# update falang_content modified 4.1 version
# Already in 4.1.0.sql but seem necessary
ALTER TABLE `#__falang_content` MODIFY `modified` datetime NULL DEFAULT NULL;