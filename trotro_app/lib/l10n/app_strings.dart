enum AppLanguage {
  english('en', 'EN'),
  twi('tw', 'TW');

  const AppLanguage(this.code, this.shortLabel);

  final String code;
  final String shortLabel;

  AppLanguage get next =>
      this == AppLanguage.english ? AppLanguage.twi : AppLanguage.english;
}

class AppStrings {
  const AppStrings(this.language);

  final AppLanguage language;

  static AppStrings of(AppLanguage language) => AppStrings(language);

  bool get isTwi => language == AppLanguage.twi;

  String _t(String en, String tw) => isTwi ? tw : en;

  // Brand
  String get appName => 'Trotro';
  String get passengerTag => _t('PASSENGER APP', 'AKWANTUFOƆ APP');
  String get driverTag => _t('DRIVER PORTAL', 'DRIVER PORTAL');
  String get passenger => _t('Passenger', 'Akwantufoɔ');
  String get driver => _t('Driver', 'Driver');
  String get languageLabel => _t('Language', 'Kasa');
  String get languageChanged => _t('Language changed', 'Kasa asesa');

  // Login
  String get welcomeBack => _t('Welcome back', 'Akwaaba bio');
  String get signIn => _t('Sign in', 'Kɔ mu');
  String get createAccount => _t('Create account', 'Bue akontaabu');
  String get signInToRide => _t('Sign in to ride', 'Kɔ mu na fa trotro');
  String get signInToDrive => _t('Sign in to drive', 'Kɔ mu na ka trotro');
  String get createPassengerAccount =>
      _t('Create a passenger account', 'Bue akwantufoɔ akontaabu');
  String get createDriverAccount =>
      _t('Create a driver account', 'Bue driver akontaabu');
  String get formSubtitleSignIn => _t(
        'Use the email and password you signed up with.',
        'Fa email ne password a wode buee akontaabu no.',
      );
  String get formSubtitleRegister => _t(
        "Just a few details and you're ready to go.",
        'Nsɛm kakra bi na woafi ase.',
      );
  String get headlineRide => _t('Ride with Trotro', 'Fa Trotro kɔ');
  String get headlineDrive => _t('Drive with Trotro', 'Ka Trotro na nya sika');
  String get subtitleLoginPassenger => _t(
        'Sign in to find a trotro, plan trips and pay fares.',
        'Kɔ mu na hwehwɛ trotro, yɛ wo akwantu nhyehyɛeɛ na tua fare.',
      );
  String get subtitleLoginDriver => _t(
        'Sign in to go online, manage trips and track earnings.',
        'Kɔ mu na bɛyɛ adwuma, hwɛ wo akwantu ne sika.',
      );
  String get subtitleRegisterPassenger => _t(
        'Create an account and get moving in minutes.',
        'Bue akontaabu na fi ase ntɛm.',
      );
  String get subtitleRegisterDriver => _t(
        'Create a driver account and start earning on every trip.',
        'Bue driver akontaabu na nya sika wɔ akwantu biara so.',
      );
  String get fullName => _t('Full name', 'Wo din nyinaa');
  String get phoneNumber => _t('Phone number', 'Telefon nɔma');
  String get email => _t('Email', 'Email');
  String get password => _t('Password', 'Password');
  String get passwordHint =>
      _t('Use at least 8 characters', 'Fa nsɛnkyerɛnne 8 anaa nea ɛboro saa');
  String get driverNote => _t(
        'After signing in, add your licence, Ghana Card and vehicle from the driver dashboard.',
        'Woakɔ mu a, ka wo licence, Ghana Card ne kar ho nsɛm wɔ driver dashboard so.',
      );
  String get secureFooter => _t('Secure sign-in · Trotro', 'Banbɔ mu kɔ mu · Trotro');
  String get showPassword => _t('Show password', 'Kyerɛ password');
  String get hidePassword => _t('Hide password', 'Fa password sie');
  String get enterFullName => _t('Enter your full name', 'Kyerɛw wo din nyinaa');
  String get enterPhone => _t('Enter your phone number', 'Kyerɛw wo telefon nɔma');
  String get validPhone => _t('Enter a valid phone number', 'Kyerɛw telefon nɔma a ɛyɛ');
  String get enterEmail => _t('Enter your email', 'Kyerɛw wo email');
  String get validEmail =>
      _t('Enter a valid email address', 'Kyerɛw email a ɛyɛ');
  String get enterPassword => _t('Enter your password', 'Kyerɛw wo password');
  String get minPasswordHint =>
      _t('Use at least 8 characters', 'Fa nsɛnkyerɛnne 8 anaa nea ɛboro saa');
  String get signedInAsDriver =>
      _t('Signed in with a driver account', 'Wokɔɔ mu wɔ driver akontaabu mu');
  String get signedInAsPassenger => _t(
        'Signed in with a passenger account',
        'Wokɔɔ mu wɔ akwantufoɔ akontaabu mu',
      );

  // Profile
  String get myProfile => _t('My profile', 'Me profaese');
  String get editProfile => _t('Edit profile', 'Sesa me profaese');
  String get profileHint =>
      _t('Tap your photo to change it', 'Pia wo mfonini na sesa');
  String get changePhoto => _t('Change photo', 'Sesa mfonini');
  String get saveChanges => _t('Save changes', 'Sie nsakraeɛ');
  String get saving => _t('Saving…', 'Ɛresie…');
  String get uploading => _t('Uploading…', 'Ɛrekɔ…');
  String get profileUpdated => _t('Profile updated', 'Wo profaese asesa');
  String get profilePhotoUpdated => _t('Photo updated', 'Mfonini asesa');
  String get photoTooLarge => _t(
        'That photo is too large. Pick one under 2 MB.',
        'Mfonini no kɛse dodo. Paw bi a ɛwɔ ase 2 MB.',
      );
  String get photoPickFailed => _t(
        'Could not open your photos. Try again.',
        'Yɛantumi anyi mfonini. Sɔ hwɛ bio.',
      );
  String get profileUpdateFailed => _t(
        'Could not save your changes. Try again.',
        'Yɛantumi ansie nsakraeɛ no. Sɔ hwɛ bio.',
      );
  String get yourDetails => _t('Your details', 'Wo nsɛm');
  String get profileEmailHint =>
      _t('This is how you sign in', 'Wo fa eyi kɔ mu');
  String get phoneOptional =>
      _t('Phone number (optional)', 'Telefon nɔma (sɛ ɛwɔ hɔ)');

  // Home
  String greeting(String? name) {
    if (name == null || name.isEmpty) return _t('Hello', 'Akwaaba');
    return _t('Hello, $name', 'Akwaaba, $name');
  }

  String get whereTo => _t('Where do you want to go?', 'Wokɔ he?');
  String get whereToHint =>
      _t('Tap a stop to see routes and fares.', 'Pia beaeɛ bi na hwɛ akwan ne fare.');
  String get from => _t('From', 'Fi');
  String get chooseWhereYouAre => _t('Choose where you are', 'Paw beaeɛ a wote');
  String get useMyLocation => _t('Use my location', 'Fa me beaeɛ');
  String get change => _t('Change', 'Sesa');
  String get recent => _t('Recent', 'Deɛ wokɔɔ kan');
  String get popularStops => _t('Popular stops', 'Beaeɛ a agye din');
  String get bookRide => _t('Book a ride', 'Frɛ trotro');
  String get myWallet => _t('My wallet', 'Me sika');
  String get logOut => _t('Log out', 'Fi mu');
  String get logOutConfirmTitle => _t('Log out?', 'Wofi mu?');
  String get logOutConfirmBody => _t(
        'You will need your email and password to sign in again.',
        'Wobɛhia email ne password na woakɔ mu bio.',
      );
  String get cancel => _t('Cancel', 'Gyae');
  String get voice => _t('Voice', 'Nne');
  String get voiceOn => _t('Voice on', 'Nne abue');
  String get voiceOff => _t('Voice off', 'Nne akyi');
  String get listen => _t('Listen', 'Tie');
  String get loadingStops => _t('Loading stops…', 'Yɛrehyɛ stops…');
  String get errorLoadingStops =>
      _t('Could not load stops.', 'Yɛantumi anhyɛ stops.');
  String get retry => _t('Retry', 'Sɔ hwɛ bio');
  String get noRoutes => _t('No routes found', 'Yenhunu kwan biara');
  String get noRoutesHint =>
      _t('Tap another stop to try again.', 'Pia beaeɛ foforɔ na sɔ hwɛ bio.');
  String get searchingRoutes => _t('Finding routes…', 'Yɛrehwehwɛ akwan…');
  String get locationDenied => _t(
        'Location is off. Choose your stop instead.',
        'Beaeɛ kwan nni hɔ. Paw wo stop.',
      );
  String get locationUnavailable => _t(
        'Could not find your location. Choose your stop instead.',
        'Yɛanhunu wo beaeɛ. Paw wo stop.',
      );
  String get sameStop => _t(
        'Start and destination must be different.',
        'Beaeɛ a wofi ne beaeɛ a wokɔ nyɛ pɛ.',
      );
  String get checkingAccount =>
      _t('Checking your account…', 'Yɛrehwɛ wo akontaabu…');
  String get searchFailed => _t(
        'Could not load routes. Check your connection and try again.',
        'Yɛantumi annya akwan. Hwɛ intanɛt na sɔ hwɛ bio.',
      );

  String originSet(String name) => _t('Starting from $name', 'Wofi $name');

  String destinationSet(String name) => _t('Going to $name', 'Wokɔ $name');

  String routesTo(String name, int count) =>
      _t('Routes to $name · $count', 'Akwan a ɛkɔ $name · $count');

  String minutes(int minutes) => _t('$minutes min', 'simma $minutes');

  String searchSummary(int count, String destination) => _t(
        '$count ${count == 1 ? 'route' : 'routes'} found to $destination.',
        'Yahunu akwan $count a ɛkɔ $destination.',
      );

  String routeSpeech({
    required String name,
    String? from,
    String? to,
    int? minutes,
    double? fare,
  }) {
    final parts = <String>[];
    if (isTwi) {
      parts.add('Kwan $name');
      if (from != null && to != null) parts.add('Fi $from kɔ $to');
      if (minutes != null) parts.add('Ɛbɛyɛ simma $minutes');
      if (fare != null) parts.add('Fare GHS ${fare.toStringAsFixed(2)}');
    } else {
      parts.add('Route $name');
      if (from != null && to != null) parts.add('From $from to $to');
      if (minutes != null) parts.add('About $minutes minutes');
      if (fare != null) parts.add('Fare ${fare.toStringAsFixed(2)} cedis');
    }
    return '${parts.join('. ')}.';
  }

  // Ride booking & payment
  String get hearInstructions => _t('Hear instructions', 'Tie akwankyerɛ');
  String get homeHelpSpeech => _t(
        'Tap the orange card to choose where you are. Then tap the picture of where you want to go. I will read the routes and fares for you.',
        'Pia orange card no na paw beaeɛ a wote. Na pia mfonini a ɛkyerɛ beaeɛ a wokɔ. Mɛkenkan akwan ne fare ama wo.',
      );
  String get rideBookingHint => _t(
        'Choose where you are, then where you are going.',
        'Paw beaeɛ a wote, na paw beaeɛ a wokɔ.',
      );
  String get pickupLocation => _t('Pickup', 'Beae a wofii');
  String get destinationLocation => _t('Destination', 'Beae a wokɔ');
  String get tapPickup => _t('Tap to choose pickup', 'Pia na paw beae a wofii');
  String get tapDestination =>
      _t('Tap to choose destination', 'Pia na paw beae a wokɔ');
  String get choosePickupTitle => _t('Where are you now?', 'Wote he seisei?');
  String get chooseDestinationTitle =>
      _t('Where are you going?', 'Wokɔ he?');
  String get faresFromPickup => _t(
        'Fares are from your pickup stop.',
        'Fare no fi beae a wofii.',
      );
  String get selectBothLocations => _t(
        'Choose your pickup and destination first.',
        'Paw beae a wofii ne beae a wokɔ kan.',
      );
  String get paymentMethodTitle => _t('How will you pay?', 'Wobɛtua dɛn?');
  String get cash => _t('Cash', 'Sika');
  String get cashHint =>
      _t('Pay the driver directly', 'Tua driver no sika no');
  String get walletHint => _t(
        'Fare comes from your wallet',
        'Fare no fi wo sika mu',
      );
  String get requestRide => _t('Find a trotro', 'Hwehwɛ trotro');
  String get requestingRide =>
      _t('Looking for a driver…', 'Yɛrehwehwɛ driver…');
  String get rideRequested =>
      _t('Ride requested. A driver will answer soon.', 'Woatɔ trotro. Driver bɛba ntɛm.');
  String get rideRequestFailed => _t(
        'Could not request the ride. Check your connection and try again.',
        'Yɛantumi antɔ trotro. Hwɛ intanɛt na sɔ hwɛ bio.',
      );
  String get tripStatus => _t('Trip status', 'Akwantu tebea');
  String get driverLabel => _t('Driver', 'Driver');
  String get fareLabel => _t('Fare', 'Fare');
  String get payNow => _t('Pay now', 'Tua seisei');
  String get paymentCompleted =>
      _t('Payment completed', 'Woatua fare no');
  String get paymentTitle => _t('Payment', 'Tuatua');
  String get rideSummary => _t('Ride summary', 'Akwantu nsɛm');
  String get totalFare => _t('Total fare', 'Fare nyinaa');
  String get payFromWallet => _t('Pay from wallet', 'Tua fi sika mu');
  String get confirmCash =>
      _t('Confirm cash payment', 'Gye sika no tom');
  String get cashConfirmNote => _t(
        'Tap only after you hand the money to the driver.',
        'Pia ara kosi woama driver no sika no.',
      );
  String get walletConfirmNote => _t(
        'The fare is taken from your wallet and paid to the driver.',
        'Woyi fare no fi wo sika mu na wotua driver no.',
      );
  String get paymentFailed =>
      _t('Payment failed. No money was taken.', 'Tuatua no ankɔ yie. Sika biara nfi hɔ.');
  String get processing => _t('Processing…', 'Yɛreyɛ…');

  String rideOriginSpeech(String origin) =>
      _t('Pickup: $origin.', 'Beae a wofii: $origin.');
  String rideRouteSpeech(String from, String to) =>
      _t('From $from to $to.', 'Fi $from kɔ $to.');
  String rideFareSpeech(double fare) => _t(
        'The fare is ${fare.toStringAsFixed(2)} cedis.',
        'Fare no yɛ ${fare.toStringAsFixed(2)} cedis.',
      );
  String rideStatusLabel(String status) {
    switch (status) {
      case 'accepted':
        return _t('Driver is coming', 'Driver no reba');
      case 'driver_arrived':
        return _t('Driver has arrived', 'Driver no abedu');
      case 'in_progress':
        return _t('Trip in progress', 'Akwantu rekɔ so');
      case 'completed':
        return _t('Trip completed', 'Akwantu no awie');
      case 'cancelled':
        return _t('Trip cancelled', 'Wɔagyae akwantu no');
      default:
        return _t('Looking for a driver', 'Yɛrehwehwɛ driver');
    }
  }

  // Driver verification & vehicle onboarding
  String get driverVerification =>
      _t('Driver verification', 'Driver nsɛm hwɛ');
  String get verificationIntro => _t(
        'Add your driver licence, Ghana Card and photos. An admin reviews them before you can take rides.',
        'Fa wo driver licence, Ghana Card ne mfonini. Admin hwɛ so ansa na woatumi agye akwantu.',
      );
  String get licenseDetails => _t('Driver licence', 'Driver licence');
  String get licenseNumber => _t('Licence number', 'Licence nɔma');
  String get enterLicenseNumber =>
      _t('Enter your licence number', 'Kyerɛw wo licence nɔma');
  String get licenseExpiry => _t('Licence expiry', 'Licence bɛdii da');
  String get enterLicenseExpiry =>
      _t('Choose the expiry date', 'Paw da a ɛbɛdii');
  String get licensePhoto => _t('Licence photo', 'Licence mfonini');
  String get ghanaCardDetails => _t('Ghana Card', 'Ghana Card');
  String get ghanaCardNumber => _t('Ghana Card number', 'Ghana Card nɔma');
  String get enterGhanaCardNumber =>
      _t('Enter your Ghana Card number', 'Kyerɛw wo Ghana Card nɔma');
  String get ghanaCardPhoto => _t('Ghana Card photo', 'Ghana Card mfonini');
  String get choosePhoto => _t('Choose photo', 'Paw mfonini');
  String get changePhotoShort => _t('Change', 'Sesa');
  String get addPhoto => _t('Add photo', 'Ka mfonini');
  String get documentTooLarge => _t(
        'That photo is too large. Pick one under 4 MB.',
        'Mfonini no kɛse dodo. Paw bi a ɛwɔ ase 4 MB.',
      );
  String get photoPickFailedRetry => _t(
        'Could not open your photos. Try again.',
        'Yɛantumi anyi mfonini. Sɔ hwɛ bio.',
      );
  String get photoNeeded => _t(
        'Add this photo to continue.',
        'Ka mfonini yi ansa na wɔakɔ so.',
      );
  String get submitForReview => _t('Send for review', 'Soma ma wɔnhwɛ');
  String get submittingReview => _t('Sending…', 'Ɛrekɔ…');
  String get verificationSubmitted => _t(
        'Your documents were sent. Waiting for approval.',
        'Wɔasoma wo nsɛm. Yɛretwɛn gyidie.',
      );
  String get verificationSubmitFailed => _t(
        'Could not send your documents. Check your connection and try again.',
        'Yɛantumi ansoma wo nsɛm. Hwɛ intanɛt na sɔ hwɛ bio.',
      );
  String get verificationUnverified =>
      _t('Not verified yet', 'Wɔnnhyɛɛ wo nso');
  String get verificationPending =>
      _t('Waiting for approval', 'Yɛretwɛn gyidie');
  String get verificationApproved => _t('Verified', 'Wɔagye wo tom');
  String get verificationRejected => _t('Needs changes', 'Ɛhia nsakraeɛ');
  String get verificationPendingNote => _t(
        'Your documents are with an admin. You can change them after a decision.',
        'Wo nsɛm wɔ admin nkyɛn. Wobɛtumi asesa wɔwie gyidie akyi.',
      );
  String get verificationApprovedNote => _t(
        'Your driver account is verified.',
        'Wɔagye wo driver akontaabu tom.',
      );
  String get verificationRejectedNote => _t(
        'Fix the issue below and send your documents again.',
        'Siesie asɛm a ɛwɔ ase ha na soma wo nsɛm bio.',
      );
  String get adminNote => _t('Admin note', 'Admin nsɛm');
  String get licenseExpiredError => _t(
        'Choose a licence expiry date in the future.',
        'Paw da a licence bɛdii a ɛwɔ daakye.',
      );
  String get requiredField => _t('This is required', 'Eyi hia');

  String verificationStatusLabel(String status) {
    switch (status) {
      case 'approved':
        return verificationApproved;
      case 'pending':
        return verificationPending;
      case 'rejected':
        return verificationRejected;
      default:
        return verificationUnverified;
    }
  }

  String get myVehicle => _t('My vehicle', 'Me kar');
  String get addVehicle => _t('Add your vehicle', 'Ka wo kar');
  String get editVehicle => _t('Edit vehicle', 'Sesa kar');
  String get vehicleIntro => _t(
        'Add your trotro, registration and roadworthy papers. An admin reviews them before you can go online.',
        'Ka wo trotro, registration ne roadworthy krataa. Admin hwɛ so ansa na woatumi abɛyɛ adwuma.',
      );
  String get vehicleMake => _t('Make', 'Kar din');
  String get vehicleModel => _t('Model', 'Model');
  String get vehicleYear => _t('Year', 'Afe');
  String get vehiclePlate => _t('Licence plate', 'Kar nɔma');
  String get vehicleColor => _t('Colour', 'Kɔla');
  String get vehicleCapacity => _t('Seats', 'Ntea');
  String get vehicleType => _t('Vehicle type', 'Kar su');
  String get vehiclePhoto =>
      _t('Vehicle photo', 'Kar mfonini');
  String get registrationPhoto =>
      _t('Registration paper', 'Registration krataa');
  String get roadworthyPhoto =>
      _t('Roadworthy certificate', 'Roadworthy krataa');
  String get vehicleSubmitted => _t(
        'Your vehicle was sent. Waiting for approval.',
        'Wɔasoma wo kar. Yɛretwɛn gyidie.',
      );
  String get vehicleSubmitFailed => _t(
        'Could not send your vehicle. Check your connection and try again.',
        'Yɛantumi ansoma wo kar. Hwɛ intanɛt na sɔ hwɛ bio.',
      );
  String get vehiclePendingNote => _t(
        'Your vehicle is with an admin for review.',
        'Wo kar wɔ admin nkyɛn na ɔrehwɛ.',
      );
  String get vehicleStatusApproved => _t('Vehicle approved', 'Wɔagye kar tom');
  String get vehicleStatusPending =>
      _t('Vehicle waiting for approval', 'Kar retwɛn gyidie');
  String get vehicleStatusRejected =>
      _t('Vehicle needs changes', 'Kar hia nsakraeɛ');
  String get vehicleDocuments =>
      _t('Photos and papers', 'Mfonini ne krataa');
  String get vehicleApprovedNote => _t(
        'Your vehicle is approved. You can go online.',
        'Wɔagye wo kar tom. Wobɛtumi abɛyɛ adwuma.',
      );
  String get vehicleRejectedNote => _t(
        'Fix the issue below and send your vehicle again.',
        'Siesie asɛm a ɛwɔ ase ha na soma wo kar bio.',
      );
  String get vehicleNeeded => _t(
        'Add a vehicle and get it approved before going online.',
        'Ka kar na ma wɔngye tom ansa na woabɛyɛ adwuma.',
      );
  String get onlineBlocked => _t(
        'Finish verification and vehicle approval to go online.',
        'Wie nsɛm hwɛ ne kar gyidie ansa na woabɛyɛ adwuma.',
      );
  String get readyToDrive =>
      _t('You are ready to take rides', 'Woasiesie wo ho ma akwantu');
  String get verificationStep => _t('Step 1 · Verify you', 'Step 1 · Hwɛ wo');
  String get vehicleStep => _t('Step 2 · Your vehicle', 'Step 2 · Wo kar');
  String get completeSetup => _t('Finish setup', 'Wie nhyehyɛeɛ');

  String vehicleTypeLabel(String type) {
    switch (type) {
      case 'suv':
        return _t('SUV', 'SUV');
      case 'minivan':
        return _t('Minivan / Trotro', 'Minivan / Trotro');
      case 'bus':
        return _t('Bus', 'Bus');
      default:
        return _t('Car', 'Kar');
    }
  }

  // QR passes
  String get passTitle => _t('Tap & Ride pass', 'Tap & Ride pass');
  String get passSubtitle => _t(
        'Scan the customer card and choose where they are going.',
        'Scan obiara card na paw beaeɛ a ɔrekɔ.',
      );
  String get scanCard => _t('Scan card', 'Scan card');
  String get typeCode => _t('Type code', 'Kyerɛw code');
  String get passCodeLabel => _t('Card code', 'Card code');
  String get passCodeHint => _t('TRT-ABCD-2345', 'TRT-ABCD-2345');
  String get findPass => _t('Find pass', 'Hwehwɛ pass');
  String get scanAgain => _t('Scan another card', 'Scan card foforɔ');
  String get passBalance => _t('Money on this pass', 'Sika a ɛwɔ pass yi so');
  String get chooseDestination => _t('Where are they going?', 'Wɔrekɔ he?');
  String get noRoutesForPass => _t(
        'No routes with fares yet. Ask an administrator to set fares.',
        'Akwan a fare wɔ so nni hɔ. Ka kyerɛ admin ma ɔnhyehyɛ fare.',
      );
  String get chargeFare => _t('Charge fare', 'Tua fare');
  String get chargeConfirmTitle => _t('Charge this trip?', 'Tua akwantu yi?');
  String get chargeConfirmBody => _t(
        'The fare leaves the pass immediately. Only charge once.',
        'Fare no fi pass no so ntɛm ara. Tua fare no pɛ.',
      );
  String get chargeOnce => _t('Charge fare now', 'Tua fare no seisei');
  String get charging => _t('Charging…', 'Yɛretua fare…');
  String get passSuspended => _t(
        'This pass is suspended. The customer cannot ride until an administrator reactivates it.',
        'Wɔasɔ pass yi ano. Ɔrentumi nfa bi nkɔ kosi admin bɛbue.',
      );
  String get passCharged => _t('Trip charged', 'Woatua fare no');
  String get remainingOnPass => _t('Left on pass', 'Deɛ ɔsɛe wɔ pass no so');
  String get done => _t('Done', 'Awie');
  String get passCameraUnavailable => _t(
        'Camera is not available here. Type the code from the card instead.',
        'Camera nni hɔ. Kyerɛw code a ɛwɔ card no so.',
      );
  String get passRecentTrips => _t('Recent trips', 'Akwantu a ɛtwaa mu');
  String get pinProtected => _t('PIN protected', 'PIN bɔ ho ban');
  String get pinApprovalTitle => _t('Customer approval', 'Kasatifo gyidie');
  String pinApprovalPrompt(String name) => _t(
        'Ask $name to enter their 4-digit PIN.',
        'Ka kyerɛ $name ma ɔntwerɛ ne PIN nnɔnan.',
      );
  String get pinEntryHint => _t(
        'Tap the customer\'s 4-digit PIN',
        'Twerɛ kasatifoɔ no PIN nnɔnan',
      );
  String pinWrong(int left) => _t(
        'Wrong PIN. $left ${left == 1 ? 'try' : 'tries'} left.',
        'PIN no nyɛ. $left ka.',
      );
  String get pinLockedMessage => _t(
        'Too many wrong PINs. Wait 10 minutes or ask an administrator to unlock.',
        'PIN bɔne pii. Twɛn simma 10 anaa ka kyerɛ admin ma ɔnbue.',
      );
  String get pinApprovalNote => _t(
        'The fare leaves the pass only after the PIN is accepted.',
        'Fare no fi pass no so ara kosi PIN no bɛgye.',
      );
  String pinApprovalSpeech(String name) => _t(
        'Ask $name to enter their four digit PIN to approve this trip.',
        'Ka kyerɛ $name ma ɔntwerɛ ne PIN nnɔnan na ɔngye akwantu yi.',
      );
  String get pinWrongSpeech => _t(
        'PIN not correct. Try again.',
        'PIN no nyɛ. Sɔ hwɛ bio.',
      );
  String get phoneApproval => _t('Phone approval', 'Phone approval');
  String get remoteApprovalSending => _t(
        'Sending approval request…',
        'Yɛresoma gyidie abisadeɛ…',
      );
  String remoteApprovalTitle(String name) => _t(
        'Waiting for $name',
        'Yɛretwɛn $name',
      );
  String remoteApprovalSent(String phone) => _t(
        'We sent a text to $phone. Ask them to reply with their 4-digit PIN.',
        'Yɛasoma SMS akɔ $phone. Ka kyerɛ wɔn ma wɔmmɛtwerɛ wɔn PIN nnɔnan.',
      );
  String get remoteApprovalNoSmartphone => _t(
        'No smartphone needed. Any phone can reply.',
        'Smartphone nni hia. Phone biara betumi agye.',
      );
  String get remoteApprovalCancelHint => _t(
        'They can reply 0 to cancel.',
        'Wobetumi atwerɛ 0 agyae.',
      );
  String get remoteApprovalExpired => _t(
        'The request expired before the customer answered. No money was taken.',
        'Gyidie abisadeɛ no bɛdii. Sika biara nfi hɔ.',
      );
  String get remoteApprovalDeclined => _t(
        'The customer declined this trip. No money was taken.',
        'Kasatifo no ampene akwantu yi. Sika biara nfi hɔ.',
      );
  String get remoteApprovalFailed => _t(
        'The charge could not be completed. No money was taken.',
        'Wontumi antua fare no. Sika biara nfi hɔ.',
      );
  String get remoteApprovalCancelled => _t(
        'The approval request was cancelled.',
        'Wɔagyae gyidie abisadeɛ no.',
      );
  String get remoteApprovalRetry => _t(
        'Send request again',
        'Soma abisadeɛ bio',
      );
  String get remoteApprovalFallback => _t(
        'Approve on this phone',
        'Gye so wɔ phone yi so',
      );
  String remoteApprovalAttemptsLeft(int left) => _t(
        '$left ${left == 1 ? 'try' : 'tries'} left for the customer.',
        '$left ka ma kasatifo no.',
      );
  String remoteApprovalSpeech(String name) => _t(
        'Waiting for $name to reply with their four digit PIN.',
        'Yɛretwɛn $name ma ɔmmɛtwerɛ ne PIN nnɔnan.',
      );

  String passGreeting(String name, double balance) => _t(
        'Pass for $name. Balance ${balance.toStringAsFixed(2)} cedis.',
        'Pass ma $name. Sika a ɛwɔ so ${balance.toStringAsFixed(2)} cedis.',
      );

  String passChargeSpeech(String name, double fare, double remaining) => _t(
        'Charged ${fare.toStringAsFixed(2)} cedis. $name can ride. ${remaining.toStringAsFixed(2)} cedis left.',
        'Woatua ${fare.toStringAsFixed(2)} cedis. $name bɛtumi akɔ. ${remaining.toStringAsFixed(2)} cedis na ɛka.',
      );
}
