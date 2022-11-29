db.createUser({
    user: "pnourrissier",
    pwd: "pwdXXX1234",
    roles: [{
        role: "readWrite",
        db: "firstmongodb"
    }]
})