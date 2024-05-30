<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'quiz_concorsi', language 'en', branch 'MOODLE_34_STABLE'
 *
 * @package   quiz_concorsi
 * @copyright 2023 UPO www.uniupo.it
 * @author    Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Archiviazione Concorsi';
$string['concorsi'] = 'Archivia file del concorso';
$string['concorsireport'] = 'Report Archiviazione Concorsi';
$string['quiznotclosed'] = 'Si prega di chiudere il quiz prima di archiviare i file del concorso';
$string['finalize'] = 'Finalizza i resoconti del concorso';
$string['areyousure'] = 'Sei sicuro di voler finalizzare i resoconti del concorso?<br>Dopo non sar&agrave; possibile modificare i resoconti.';
$string['attention'] = 'Presta attenzione';
$string['notallgraded'] = 'Alcune risposte alle domande aperte <strong>non sono state valutate</strong>.<br>Ti invitiamo a completare la valutazione prima di finalizzare.';
$string['finalizeconfirm'] = 'Finalizza';
$string['refinalize'] = 'Rifinalizza i resoconti del concorso';
$string['zip'] = 'Crea archivio ZIP delle prove';
$string['downloadgradesfile'] = 'Download file valutazioni';
$string['candidate'] = 'Candidato';
$string['attemptfiles'] = 'File delle prove ({$a})';
$string['finalizedfiles'] = 'File finalizzati';
$string['questionnumber'] = 'Domanda {$a}';
$string['filehash'] = 'Hash del file della prova';
$string['answer'] = 'Risposta data';
$string['rightanswer'] = 'Risposta esatta';
$string['gradedattempts'] = 'Prove valutate';
$string['gradebook'] = 'Valutazioni finali';
$string['grades'] = 'Valutazioni';
$string['attemptsarchive'] = 'Archivio prove';
$string['typepassword'] = 'Inserire la password per proteggere il file zip:';

$string['concorsi:archivereviews'] = 'Archivia resoconti del concorso';
$string['concorsi:downloadreviews'] = 'Download resoconti del concorso';
// PRIVACY.
$string['privacy:metadata'] = 'Il plugin Archiviazione Concorsi quiz non salva alcun dato personale degli utenti.';

$string['concorsisettings'] = 'Impostazioni Concorsi';
$string['clear'] = 'Svuota';
$string['coursestartdate'] = 'Imposta alla data di inizio corso';
$string['anonymizedates'] = 'Anonimizza le date dei tentativi';
$string['anonymizedates_desc'] = 'Se abilitato le date di tutti i tentativi dei quiz saranno cancellate eo impostate alla data di inizio del corso in modo da anonomizzare il pi&ugrave; possibile l\'attività dell\'utente';
$string['usernamehash'] = 'Aggiungi gli username mascherati nei PDF che contengono i tentativi';
$string['usernamehash_desc'] = 'Se abilitato i PDF che contegono i tentativi includeranno lo username mascherato. Questo può essere utile in caso di ripudio del tentativo';
$string['allowrefinalize'] = 'Rifinalizza';
$string['allowrefinalize_desc'] = 'Se abilitato, gli utenti abilitati potranno finalizzare pi&ugrave; di una volta i concorsi. Questo potrebbe diminuire l\'anomizzazione dei candidati';
$string['encryptzipfiles'] = 'Criptare gli zip';
$string['encryptzipfiles_desc'] = 'Se abilitato, verr&agrave; chiesta una password per criptare i file zip che contengono i PDF delle prove';
$string['suspendmode'] = 'Modalit&agrave; di sospensione';
$string['suspendmode_desc'] = 'Gli utenti dei candidati verranno sospesi alla chiusura del quiz. Puoi scegliere se sospendere tutti i candidati iscritti al corso o solo quelli che hanno tentato il quiz';
$string['attempted'] = 'Solo i candidati che hanno tentato il quiz';
$string['enrolled'] = 'Tutti i candidati iscritti al corso';
$string['closequiz'] = 'Chiudi quiz';
$string['closequizconfirm'] = 'Chiudi ora il quiz';
$string['lockout'] = 'Se confermi di chiudere questo quiz, gli studenti che stanno tentando il quiz saranno espulsi e le risposte non salvate saranno perse';
