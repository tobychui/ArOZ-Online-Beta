package main

import (
	"log"
	"encoding/json"
	"github.com/boltdb/bolt"
	"errors"

	//database "imuslab.com/aroz_online/mod/database"
)

/*
Database for ArOZ Online make use of the keydb by robaho
See more details of this database in https://github.com/boltdb/bolt

Why the developer choose this DB you might ask? 
Beacuse it is simple and simple is beautiful :)
*/

//Initiate the database object
func system_db_service_init(dbfile string) *bolt.DB{
	db, err := bolt.Open(dbfile, 0600, nil)
	if err != nil {
		log.Fatal(err)
	} 

	//Create the central system db for all system services
	err = db.Update(func(tx *bolt.Tx) error {
		_, err = tx.CreateBucketIfNotExists([]byte("SYSTEM"))
        if err != nil {
        	return err
		}
		
        return nil
	})
	
	if (err != nil){
		log.Fatal(err)
	}

	//Create a testing db object
	//dbObject, _ := database.NewDatabase(dbfile, false)
	//log.Println(dbObject);

	log.Println("ArOZ Online Key-value Database Service Loaded");
	return db;
}

/*
	Create / Drop a table
	Usage:
	err := system_db_newTable(sysdb, "MyTable")
	err := system_db_dropTable(sysdb, "MyTable")
*/
func system_db_newTable(dbObject *bolt.DB, tableName string) error{
	if (*demo_mode && !startingUp){
		return errors.New("Operation rejected in demo mode")
	}
	err := dbObject.Update(func(tx *bolt.Tx) error {
		_, err := tx.CreateBucketIfNotExists([]byte(tableName))
		if err != nil {
			return err
		}
		return nil
	})
	return err
}

func system_db_dropTable(dbObject *bolt.DB, tableName string) error{
	if (*demo_mode && !startingUp){
		return errors.New("Operation rejected in demo mode")
	}
	err := dbObject.Update(func(tx *bolt.Tx) error {
		err := tx.DeleteBucket([]byte(tableName))
		if err != nil {
			return err
		}
		return nil
	})
	return err
}
/*
	Write to database with given tablename and key. Example Usage:
	type demo struct{
		content string
	}
	thisDemo := demo{
		content: "Hello World",
	}
	err := system_db_write(sysdb, "MyTable", "username/message",thisDemo);
*/
func system_db_write(dbObject *bolt.DB, tableName string, key string, value interface{}) error{
	if (*demo_mode && !startingUp){
		return errors.New("Operation rejected in demo mode")
	}
	jsonString, err := json.Marshal(value);
	if (err != nil){
		return err
	}
	err = dbObject.Update(func(tx *bolt.Tx) error {
		_, err := tx.CreateBucketIfNotExists([]byte(tableName))
		b := tx.Bucket([]byte(tableName))
		err = b.Put([]byte(key), jsonString)
		return err
	})
	return err
}

/*
	Read from database and assign the content to a given datatype. Example Usage:

	type demo struct{
		content string
	}
	thisDemo := new(demo)
	err := system_db_write(sysdb, "MyTable", "username/message",&thisDemo);
*/
func system_db_read(dbObject *bolt.DB, tableName string, key string, assignee interface{}) error{
	err := dbObject.View(func(tx *bolt.Tx) error {
		b := tx.Bucket([]byte(tableName))
		v := b.Get([]byte(key))
		json.Unmarshal(v, &assignee)
		return nil
	})
	return err
}

/*
	Delete a value from the database table given tablename and key
	
	err := system_db_delete(sysdb, "MyTable", "username/message");
*/
func system_db_delete(dbObject *bolt.DB, tableName string, key string) error{
	if (*demo_mode && !startingUp){
		return errors.New("Operation rejected in demo mode")
	}
	err := dbObject.Update(func(tx *bolt.Tx) error {
		tx.Bucket([]byte(tableName)).Delete([]byte(key))
		return nil;
	})

	if (err != nil){
		return err
	}
	return nil
}

// ===================== SYSTEM ONLY KEY VALUE STORAGE ======================
/*
//Deprecated on 26-5-2020
func system_db_getValue(dbObject *bolt.DB, key string, assignObject interface{}) error{
	var jsonString []byte
	err := dbObject.View(func(tx *bolt.Tx) error {
		jsonString = tx.Bucket([]byte("SYSTEM")).Get([]byte(key))
		return nil
	})
	err = json.Unmarshal(jsonString, &assignObject)
	return err
}

func system_db_setValue(dbObject *bolt.DB, key string, value interface{}) bool{
	if (*demo_mode){
		return false
	}
	valueInBytes, err := json.Marshal(value)
	if (err != nil){
		log.Println("[Database] Cannot parse value for key: " + key)
		return false
	}
    err = dbObject.Update(func(tx *bolt.Tx) error {
        err = tx.Bucket([]byte("SYSTEM")).Put([]byte(key), valueInBytes)
        if err != nil {
            return err
        }
        return nil
	})
	if (err != nil){
		log.Println("[Database] Set content FAILED for key: " + key, err)
		return false
	}
	return true;
}
*/

/*
	//List table example usage
	//Assume the value is stored as a struct named "groupstruct"

	entries := system_db_listTable(sysdb, "test")
	for _, keypairs := range entries{
		log.Println(string(keypairs[0]))
		group := new(groupstruct)
		json.Unmarshal(keypairs[1], &group)
		log.Println(group);
	}
	
*/
func system_db_listTable(dbObject *bolt.DB, table string) [][][]byte{
	var results [][][]byte
	dbObject.View(func(tx *bolt.Tx) error {
		b := tx.Bucket([]byte(table))
		c := b.Cursor()
		
		for k, v := c.First(); k != nil; k, v = c.Next() {
			results = append(results, [][]byte{k, v})
		}
		return nil
	})
	return results;
}

func system_db_removeValue(dbObject *bolt.DB, key string) bool{
	if (*demo_mode){
		return false
	}
	err := dbObject.Update(func(tx *bolt.Tx) error {
		tx.Bucket([]byte("SYSTEM")).Delete([]byte(key))
		return nil;
	})

	if (err != nil){
		return false
	}
	return true
}

func system_db_closeDatabase(dbObject *bolt.DB){
	dbObject.Close()
	return;
}

