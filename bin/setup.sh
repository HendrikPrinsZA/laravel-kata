#!/usr/bin/env bash

# docker exec -it kata-mysql mysql -u root -e "CREATE USER 'root'@'192.%' IDENTIFIED BY '';GRANT ALL PRIVILEGES ON *.* TO 'root'@'192.%';FLUSH PRIVILEGES;"

docker exec -it kata-mysql mysql -u root -e "DROP DATABASE IF EXISTS testing; CREATE DATABASE testing;"

# DROP USER 'sail'@'%';
docker exec -it kata-mysql mysql -u root -e "CREATE USER 'sail'@'%' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON *.* TO 'sail'@'%'; FLUSH PRIVILEGES;"
