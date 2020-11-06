var Typed = require('typed.js');

var typed = new Typed('.typing', {
    strings: ["<b>Envoyez</b> et <b>recevez</b> des messages à grande échelle.", "Communiquez autrement avec vos <b>clients</b>.",
    "Réussissez votre <b>campagne électorale</b> à moindre coût."],
    typeSpeed: 50,
    backSpeed: 60,
    smartBackspace: true,
    contentType: 'html',
    loop: true
});