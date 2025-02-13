/*
Javascript file 
Leart Demiri - Santiago Spirig
29.01.2025
*/
"use strict"

let conModeBtn = document.getElementById("logInModeBtn");
let sinModeBtn = document.getElementById("signInModeBtn");
let signMode = "Login";

conModeBtn.addEventListener("click", () => handleFormMode("Login"));
sinModeBtn.addEventListener("click", () => handleFormMode("Sign"));

/// This function handles the user interface letting the
/// user choose between logging in or signing in
function handleFormMode(mode) {
    let connectForm = document.getElementById("connectForm");
    let signingForm = document.getElementById("signingForm");

    let conModeDiv = document.getElementById("HasAccountLogIn");
    let sinModeDiv = document.getElementById("NoAccountSignIn");

    if (mode == "Login") {
        signMode = "Login";
        connectForm.style.display = "block";
        signingForm.style.display = "none";
        conModeDiv.style.display = "none";
        sinModeDiv.style.display = "block";
    } else if (mode == "Sign") {
        signMode = "Sign";
        connectForm.style.display = "none";
        signingForm.style.display = "block";
        conModeDiv.style.display = "block";
        sinModeDiv.style.display = "none";
    } else {
        signMode = "Sign";
        connectForm.style.display = "none";
        signingForm.style.display = "block";
        conModeDiv.style.display = "block";
        sinModeDiv.style.display = "none";
        console.warn("Set to default : signing mode");
    }
}


/// This functions handles the signing in method
/// Handles the form and then sends the data to the API via post request
async function CreateAccount(event) {
    event.preventDefault();

    let email = document.getElementById("cmail");
    let username = document.getElementById("cusrname");
    let password = document.getElementById("cpswrd");
    let verifyPassword = document.getElementById("cvpwd");

    if (verifyPassword != password) { return false; }

    let formData = { email: email.value, username: username.value, password: password.value };

    let jsonEncodedData = JSON.stringify(formData);


    try {
        let response = await fetch(API_SOURCE + "create-user/", { method: 'POST', body: jsonEncodedData, });
        let data = await response.json();

        

    } catch (error) {
        OUTPUT.innerHTML += "getPosts: error " + error.message + "<br>";
        console.warn("Fetch error:", error);
    }
}

