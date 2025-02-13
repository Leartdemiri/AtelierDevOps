/*
Javascript file
Leart Demiri - Santiago Spirig
29.01.2025
*/

"use strict";

const API_SOURCE = "API/Handlers/";
const MEDIA_OUTPUT = document.getElementById("MediaList");

// let response = await fetch(API_SOURCE + "posts/", {
//     method: 'GET',
//     body: formData,});
async function ConvertToHTML(table, json) {
    switch (table) {
        case "Post":
            let html = `<div class="card contactCard bg-dark">`;
            // <div class="card-body flexCardBody">
            //                 <h5 class="card-title text-light"><?=$contact->prenom?> <?=$contact->nom?> </h5>
            //                 <img src="<?php if (filter_var($contact->image_cheminFichier, FILTER_SANITIZE_FULL_SPECIAL_CHARS) != false) {echo $contact->image_cheminFichier;} ?>" class="card-img-top bigImage" alt="Portrait">
            //             </div>
            //             <div>
            //                 <button onclick="window.location.href='/details/<?=$contact->idContact?>'" type="button" class="btn btn-primary fw-bold">Détails</button>
            //                 <button onclick="window.location.href='/modify/<?=$contact->idContact?>'" type="button" class="btn btn-warning fw-bold">Éditer</button>
            //             </div>
            //     
            html += "</div>";
            MEDIA_OUTPUT = html;
            break;
        case "Media":
        
            break;
        case "Users_has_Post":
        
            break;
        case "Users":
        
            break;
        default:
            break;
    }
}

async function fetchPosts() {
    try {
        let response = await fetch(API_SOURCE + "posts/", {
            method: 'GET',
            });

        const data = await response.json();

        console.log(JSON.stringify(data));
        
        if (JSON.stringify(data) !== null) {

            console.log("getPosts body: " + JSON.stringify(data) + "<br>");
            
            ConvertToHTML("Post", )

            if (!response.ok) {
                MEDIA_OUTPUT.innerHTML += "getPosts: 200 expected received " + printStatusAndErrorMessage(response) + "<br>";
            }
        }
        else {
            throw new Error(`Erreur au fetch : ${JSON.stringify(data)}`);
        }
    } catch (error) {
        MEDIA_OUTPUT.innerHTML += "getPosts: error: " + error.message + "<br>";
        console.warn("Fetch error:", error);
        
    }
}

function printStatusAndErrorMessage(response) {
    return response.status + (response.statusText ? ": " + response.statusText : "");
}

// Exécute fetchPosts au chargement de la page
document.addEventListener("DOMContentLoaded", () => {
    fetchPosts();
});
