<?php

$jsonCredentials = $_POST['LogIn'];
$GetUsers = $_POST['GetUsers'];
$jsonMail = $_POST['QuickMail'];
$jsonNewAccount = $_POST['NewAccount'];
$jsonInitializeContactsFile = $_POST['InitializeContactsFile'];
$jsonGetFile = $_POST['GetFile'];
$jsonSendSM = $_POST['SendSM'];
$jsonGetSMList = $_POST['GetSMList'];
$jsonDelSM = $_POST['DelSM'];
$jsonAddContact = $_POST['AddContact'];
$jsonAddPendingContact = $_POST['AddPendingContact'];
$jsonChangeUserData = $_POST['ChangeUserData'];
$jsonChangeUserPP = $_POST['ChangeUserPP'];
$jsonWriteDataFile = $_POST['WriteDataFile'];

$sFeedback = "";

if ($jsonCredentials)
	$sFeedback = LogIn($jsonCredentials);
else if ($GetUsers)
	$sFeedback = GetUsers($GetUsers);
else if ($jsonGetFile)
	$sFeedback = GetFile($jsonGetFile);
else if ($jsonNewAccount)
	$sFeedback = MakeNewAccount($jsonNewAccount);
else if ($jsonMail)
	$sFeedback = QuickMail($jsonMail);
else if ($jsonInitializeContactsFile)
	$sFeedback = InitializeContactsFile($jsonInitializeContactsFile);
else if ($jsonSendSM)
	$sFeedback = SendSM($jsonSendSM);
else if ($jsonGetSMList)
	$sFeedback = GetSMList($jsonGetSMList);
else if ($jsonDelSM)
	$sFeedback = DelSMMsg($jsonDelSM);
else if ($jsonAddContact)
	$sFeedback = AddContact($jsonAddContact);
else if ($jsonAddPendingContact)
	$sFeedback = AddPendingContact($jsonAddPendingContact);
else if ($jsonChangeUserData)
	$sFeedback = ChangeUserData($jsonChangeUserData);
else if ($jsonChangeUserPP)
	$sFeedback = ChangeUserPP($jsonChangeUserPP);
else if ($jsonWriteDataFile)
	$sFeedback = WriteDataFile($jsonWriteDataFile);

echo $sFeedback;



function LogIn($jsonCredentials) {
	$objCredentials = json_decode($jsonCredentials);
	$jsonUsers = file_get_contents("Data/Users.json");
	$objUsers = json_decode($jsonUsers);
	$nUsers = count($objUsers->Users);
	for ($i=0; $i<$nUsers; $i++) {
		if ($objCredentials->hUN === $objUsers->Users[$i]->hUN && 
				$objCredentials->hPP === $objUsers->Users[$i]->hPP) {
			return json_encode($objUsers->Users[$i]);
		}
	}
	return "";
}

function GetUsers($GetUsers) {
	if ("GetUsers" === $GetUsers)
		return file_get_contents("Data/Users.json");
}

function GetFile($jsonGetFile) {
	$objGetFile = json_decode($jsonGetFile);
	if ("Contacts" === $objGetFile->FileName)
		return file_get_contents("Data/Messages/" . strval($objGetFile->ID) . "/Contacts.json");
	else if ("Users" === $objGetFile->FileName)
		return file_get_contents("Data/Users.json");
	else if ("PendingContacts" === $objGetFile->FileName)
		return file_get_contents("Data/Messages/" . strval($objGetFile->ID) . "/PendingContacts.json");
	else if ("Passwords" === $objGetFile->FileName)
		return file_get_contents("Data/Messages/" . strval($objGetFile->ID) . "/Passwords.json");
	else if ("Notes" === $objGetFile->FileName)
		return file_get_contents("Data/Messages/" . strval($objGetFile->ID) . "/Notes.json");
	
	return "";
}

function MakeNewAccount($jsonNewAccount) {
	date_default_timezone_set ("America/Los_Angeles");
	$objNewUser = json_decode($jsonNewAccount);
	$objNewUser->ID = number_format(microtime(true)*1000,0,'.','');
	$jsonExistingUsers = file_get_contents("Data/Users.json");
	$objExistingUsers = json_decode($jsonExistingUsers);
	$objExistingUsers->Users[] = $objNewUser;
	$jsonExistingUsers = json_encode($objExistingUsers,  JSON_PRETTY_PRINT);
	$nWritten = file_put_contents("Data/Users.json", $jsonExistingUsers, LOCK_EX);
	$objPendingContacts = [];
	$jsonPendingContacts = json_encode($objPendingContacts);
	mkdir("Data/Messages/" . strval($objNewUser->ID));
	mkdir("Data/Messages/" . strval($objNewUser->ID) . "/Sent/");
	mkdir("Data/Messages/" . strval($objNewUser->ID) . "/Inbox/");
	file_put_contents("Data/Messages/" . strval($objNewUser->ID) . "/PendingContacts.json", $jsonPendingContacts, LOCK_EX);
	//$Contacts = "[]" . PHP_EOL;
	//file_put_contents("/home2/silentph/public_html/eyesonlymail/Data/Messages/" . strval($objNewUser->ID) . "/Contacts.json", $Contacts, LOCK_EX);
	return strval($objNewUser->ID);
}

function QuickMail($jsonMail) {
	$objMail = json_decode($jsonMail);
	return mail ($objMail->To, $objMail->Subject, rawurldecode($objMail->Body), $objMail->Headers);
}

function InitializeContactsFile($jsonInitializeContactsFile) {
	$objInitializeContactsFile = json_decode($jsonInitializeContactsFile);
	return file_put_contents("Data/Messages/" . strval($objInitializeContactsFile->ID) . "/Contacts.json", $objInitializeContactsFile->eMe, LOCK_EX);
}

function SendSM($jsonSendSM) {
	date_default_timezone_set ("America/Los_Angeles");
	$objSendSM = json_decode($jsonSendSM);
	return file_put_contents("Data/Messages/" . strval($objSendSM->To) . "/Inbox/" . strval($objSendSM->Date) . ".json", $jsonSendSM, LOCK_EX);
}

function GetSMList($jsonGetSMList) {
	$objGetSMList = json_decode($jsonGetSMList);
	$aMsgs = scandir("Data/Messages/" . strval($objGetSMList->ID) . "/Inbox/", SCANDIR_SORT_DESCENDING);
	$nMsg = count($aMsgs);
	$nMsg = ($nMsg > $objGetSMList->Last) ? $objGetSMList->Last : $nMsg;
	$objList = new stdClass();
	$objList->Count = $nMsg-2;
	$objList->First = $objGetSMList->First;
	$objList->Last = $objGetSMList->Last;
	$objList->Location = $objGetSMList->Location;
	$objList->Msgs = [];
	for ($i=$objGetSMList->First; $i<$nMsg; $i++) {
		if ("." != $aMsgs[$i] && ".." != $aMsgs[$i]) {
			$objList->Msgs[] = new stdClass();
			$nMsgCount = count($objList->Msgs) -1;
			$objList->Msgs[$nMsgCount]->Date = substr($aMsgs[$i], 0, 13);
			$objList->Msgs[$nMsgCount]->Data = file_get_contents("Data/Messages/" . strval($objGetSMList->ID) . "/Inbox/" . $aMsgs[$i]);
		}
	}
	$jsonMsgs = json_encode($objList);
	return $jsonMsgs;
}

function DelSMMsg($jsonDelSM) {
	$objDelSM = json_decode($jsonDelSM);
	return unlink ("Data/Messages/" . strval($objDelSM->UserID) . "/Inbox/" . strval($objDelSM->MsgToDelete) . ".json");
}

function AddContact($jsonAddContact) {
	$objAddContact = json_decode($jsonAddContact);
	// Add data to new contacts pending contacts file
	$jsonPendingContacts = file_get_contents("Data/Messages/" . strval($objAddContact->NewContactID) . "/PendingContacts.json");
	$objPendingContacts = json_decode($jsonPendingContacts);
	$objPendingContacts[] = $objAddContact->PendingEntry;
	$jsonRevPendingContacts = json_encode($objPendingContacts);
	$nPending = file_put_contents("Data/Messages/" . strval($objAddContact->NewContactID) . "/PendingContacts.json", $jsonRevPendingContacts, LOCK_EX);
	
	// Replace your own contacts file
	$nContacts = file_put_contents("Data/Messages/" . strval($objAddContact->UserID) . "/Contacts.json", $objAddContact->NewContactsFile, LOCK_EX);
	if ($nPending && $nContacts)
		return $nContacts;
	else
		return "";
}

function AddPendingContact($jsonAddPendingContact) {
	// Add a pending contact to your contacts list
	$objAddPendingContact = json_decode($jsonAddPendingContact);
	$nContacts = file_put_contents("Data/Messages/" . strval($objAddPendingContact->UserID) . "/Contacts.json", $objAddPendingContact->NewContactsFile, LOCK_EX);
	$objEmptyPendingContacts = [];
	$jsonEmptyPendingContacts = json_encode($objEmptyPendingContacts);
	$nPending = file_put_contents("Data/Messages/" . strval($objAddPendingContact->UserID) . "/PendingContacts.json", $jsonEmptyPendingContacts, LOCK_EX);
	if ($nContacts && $nPending)
		return 1;
	else
		return 0;
}

function WriteDataFile($jsonWriteDataFile) {
	$objWriteDataFile = json_decode($jsonWriteDataFile);
	if ("Contacts" === $objWriteDataFile->FileName && $objWriteDataFile->NewContactsFile)
		return file_put_contents("Data/Messages/" . strval($objWriteDataFile->UserID) . "/Contacts.json", $objWriteDataFile->NewContactsFile, LOCK_EX);
	else if ("Message" === $objWriteDataFile->FileName) {
		$objData = json_decode($objWriteDataFile->Data);
		$jsonData = json_encode($objData);
		return file_put_contents("Data/Messages/" . strval($objWriteDataFile->UserID) . "/Inbox/" . strval($objData->Date) . ".json", $jsonData, LOCK_EX);
	}
	else if ("Passwords" === $objWriteDataFile->FileName && $objWriteDataFile->PasswordsFile)
		return file_put_contents("Data/Messages/" . strval($objWriteDataFile->UserID) . "/Passwords.json", $objWriteDataFile->PasswordsFile, LOCK_EX);
	else if ("Notes" === $objWriteDataFile->FileName && $objWriteDataFile->NotesFile)
		return file_put_contents("Data/Messages/" . strval($objWriteDataFile->UserID) . "/Notes.json", $objWriteDataFile->NotesFile, LOCK_EX);
	return "";
}

function ChangeUserData($jsonChangeUserData) {
	$objChangeUserData = json_decode($jsonChangeUserData);
	$jsonUsers = file_get_contents("Data/Users.json");
	$objUsers = json_decode($jsonUsers);
	$nCountUsers = count($objUsers->Users);
	for ($i=0; $i<$nCountUsers; $i++) {
		if ($objUsers->Users[$i]->ID == $objChangeUserData->ID) {
			$objUsers->Users[$i] = $objChangeUserData;
			$jsonRevisedUsers = json_encode($objUsers);
			$nWritten = file_put_contents("Data/Users.json", $jsonRevisedUsers, LOCK_EX);
			return $nWritten;
		}
	}
	return "";
}

function ChangeUserPP($jsonChangeUserPP) {
	$sContacts = '';
	$objChangeUserPP = json_decode($jsonChangeUserPP);
	$jsonUsers = file_get_contents("Data/Users.json");
	$objUsers = json_decode($jsonUsers);
	$nCountUsers = count($objUsers->Users);
	for ($i=0; $i<$nCountUsers; $i++) {
		if ($objUsers->Users[$i]->ID == $objChangeUserPP->ID) {
			$objUsers->Users[$i]->hPP = $objChangeUserPP->hPP;
			$objUsers->Users[$i]->pWords = $objChangeUserPP->pWords;
			$objUsers->Users[$i]->pName = $objChangeUserPP->pName;
			$objUsers->Users[$i]->pColor = $objChangeUserPP->pColor;
			$sContacts = $objChangeUserPP->pContacts;
			$jsonRevisedUsers = json_encode($objUsers);
			$nWritten1 = file_put_contents("Data/Messages/" . strval($objChangeUserPP->ID) . "/Contacts.json", $objChangeUserPP->pContacts, LOCK_EX);
			$nWritten2 = file_put_contents("Data/Messages/" . strval($objChangeUserPP->ID) . "/Passwords.json", $objChangeUserPP->pPasswords, LOCK_EX);
			$nWritten3 = file_put_contents("Data/Messages/" . strval($objChangeUserPP->ID) . "/Notes.json", $objChangeUserPP->pNotes, LOCK_EX);
			$nWritten = file_put_contents("Data/Users.json", $jsonRevisedUsers, LOCK_EX);
			return $nWritten;
		}
	}
	return "";
}


?>
