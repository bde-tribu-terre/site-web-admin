function surligne(input, erreur) {
    if (erreur) {
        input.classList.remove('input-valide');
        input.classList.add('input-invalide')
    } else {
        input.classList.remove('input-invalide');
        input.classList.add('input-valide')
    }
}

function verifForm(form) {
    let elements = form.elements;
    for (let i = 0; i < elements.length; i++) {
        if (elements[i].type !== 'submit') {
            elements[i].onblur(elements[i]);
        }
    }

    let strInvalides = '';
    let tousOk = true;
    for (let i = 0; i < elements.length; i++) {
        if (elements[i].type !== 'submit' && !elements[i].classList.contains('input-valide')) {
            if (elements[i].labels[0] === undefined) {
                alert(elements[i].name);
            }
            strInvalides += '\n- ' + elements[i].labels[0].innerText;
            tousOk = false;
        }
    }
    if (!tousOk) {
        alert('Certain champs n\'ont pas été saisis correctement :' + strInvalides)
    }
    return tousOk;
}

function verifNonVide(input) {
    surligne(
        input,
        input.value === ''
    );
}

function verifMdpIdentique(input) {
    surligne(
        input,
        input.value === '' || input.value !== document.getElementById(input.id.replace('_verif', '')).value
    );
}

function verifNonMoins1(input) {
    surligne(
        input,
        input.value === '-1'
    );
}

function garderMinuscules(input) {
    input.value = input.value.toLowerCase();
}

function garderMoins(input, nb) {
    if (input.value.length > nb) {
        input.value = input.value.substr(0, nb);
    }
}

function decoder(target, type_balise) {
    let elementBase64 = document.getElementById(target + '_base64');
    let strBase64 = elementBase64.innerText;
    let strMail = atob(strBase64);
    let elementDiv = document.getElementById(target + '_div');
    elementDiv.removeChild(elementBase64);

    let elementMail = document.createElement(type_balise);
    elementMail.setAttribute('id', target + '_mail');
    elementMail.innerText = strMail;
    elementDiv.appendChild(elementMail);

    let elementButton = document.getElementById(target + '_button');
    elementButton.setAttribute('disabled', '');
}
