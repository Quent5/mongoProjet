db.createUser({
    user: "super",
    pwd: "route",
    roles: [{
        role: "readWrite",
        db: "firstmongodb"
    }]
})