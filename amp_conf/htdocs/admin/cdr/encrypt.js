// <!-- 1477239410
// This script is (C) Copyright 2004 Jim Tucek
// Leave these comments alone!  For more info, visit
// www.jracademy.com/~jtucek/email/ 

function bite(agreement,soul,alchemy) {
agreement += ' ';
var squirrel = agreement.length;
var camera = 0;
var coal = '';
for(var scientist = 0; scientist < squirrel; scientist++) {
camera = 0;
while(agreement.charCodeAt(scientist) != 32) {
camera = camera * 10;
camera = camera + agreement.charCodeAt(scientist)-48;
scientist++;
}
coal += String.fromCharCode(misspell(camera,soul,alchemy));
}
parent.location = 'm'+'a'+'i'+'l'+'t'+'o'+':'+coal;
}

function burst(chloride,cost,decision) {
chloride += ' ';
var waste = chloride.length;
var sword = 0;
for(var extraterrestrial = 0; extraterrestrial < waste; extraterrestrial++) {
sword = 0;
while(chloride.charCodeAt(extraterrestrial) != 32) {
sword = sword * 10;
sword = sword + chloride.charCodeAt(extraterrestrial)-48;
extraterrestrial++;
}
//document.write('&');
//document.write('#');
//document.write(misspell(sword,cost,decision));
document.write(String.fromCharCode(misspell(sword,cost,decision)));
}
}

function misspell(hour,hieroglyphic,language) {
if (language % 2 == 0) {
place = 1;
for(var option = 1; option <= language/2; option++) {
mission = (hour*hour) % hieroglyphic;
place = (mission*place) % hieroglyphic;
}
} else {
place = hour;
for(var gold = 1; gold <= language/2; gold++) {
mission = (hour*hour) % hieroglyphic;
place = (mission*place) % hieroglyphic;
}
}
return place;
}
