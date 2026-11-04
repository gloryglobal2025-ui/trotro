import 'package:flutter/material.dart';
import '../models/wallet.dart';
import '../services/api_service.dart';

enum _TxFilter { all, moneyIn, moneyOut }

class WalletScreen extends StatefulWidget {
  final bool isDriver;

  const WalletScreen({super.key, this.isDriver = false});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  WalletSummary? _summary;
  bool _isLoading = true;
  String? _error;
  bool _hideBalance = false;
  _TxFilter _filter = _TxFilter.all;
  final GlobalKey _transactionsKey = GlobalKey();
  final ScrollController _scrollController = ScrollController();

  static const _income = Color(0xFF1E9E57);
  static const _expense = Color(0xFFD64545);
  static const _pending = Color(0xFFB26A00);

  Color get _accent =>
      widget.isDriver ? const Color(0xFF1B8A4C) : const Color(0xFFF57C00);

  List<Color> get _gradient => widget.isDriver
      ? const [Color(0xFF0E5A34), Color(0xFF2E9E5B)]
      : const [Color(0xFFE65100), Color(0xFFFFA000)];

  @override
  void initState() {
    super.initState();
    _loadWallet();
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _loadWallet() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final summary = await ApiService.getWallet();
      if (!mounted) return;
      setState(() {
        _summary = summary;
        _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  void _scrollToTransactions() {
    final ctx = _transactionsKey.currentContext;
    if (ctx != null) {
      Scrollable.ensureVisible(
        ctx,
        duration: const Duration(milliseconds: 400),
        curve: Curves.easeInOut,
      );
    }
  }

  Future<void> _showDepositSheet() async {
    final amountController = TextEditingController();
    final referenceController = TextEditingController();
    final notesController = TextEditingController();
    var method = 'momo';
    var isSubmitting = false;
    final theme = Theme.of(context);

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
              ),
              child: Container(
                decoration: BoxDecoration(
                  color: theme.colorScheme.surface,
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(28),
                  ),
                ),
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const _SheetHandle(),
                      const SizedBox(height: 20),
                      _SheetTitle(
                        icon: Icons.add_card_rounded,
                        accent: _accent,
                        title: 'Add money',
                        subtitle: 'Top up with Mobile Money or a bank transfer.',
                      ),
                      const SizedBox(height: 20),
                      SegmentedButton<String>(
                        segments: const [
                          ButtonSegment(
                            value: 'momo',
                            icon: Icon(Icons.phone_android, size: 18),
                            label: Text('Mobile Money'),
                          ),
                          ButtonSegment(
                            value: 'bank',
                            icon: Icon(Icons.account_balance, size: 18),
                            label: Text('Bank'),
                          ),
                        ],
                        selected: {method},
                        showSelectedIcon: false,
                        onSelectionChanged: (value) =>
                            setSheetState(() => method = value.first),
                      ),
                      const SizedBox(height: 16),
                      TextField(
                        controller: amountController,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        decoration: const InputDecoration(
                          labelText: 'Amount',
                          prefixText: 'GHS ',
                          border: OutlineInputBorder(),
                        ),
                      ),
                      const SizedBox(height: 10),
                      Wrap(
                        spacing: 8,
                        runSpacing: 6,
                        children: [20, 50, 100, 200]
                            .map(
                              (amount) => ActionChip(
                                label: Text('GHS $amount'),
                                onPressed: () => setSheetState(
                                  () => amountController.text = '$amount',
                                ),
                              ),
                            )
                            .toList(),
                      ),
                      const SizedBox(height: 16),
                      TextField(
                        controller: referenceController,
                        decoration: const InputDecoration(
                          labelText: 'Payment reference',
                          hintText: 'MoMo or bank transaction ID',
                          border: OutlineInputBorder(),
                        ),
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: notesController,
                        decoration: const InputDecoration(
                          labelText: 'Notes (optional)',
                          border: OutlineInputBorder(),
                        ),
                      ),
                      const SizedBox(height: 16),
                      const _InfoNote(
                        text:
                            'Your balance is credited once an administrator verifies the payment.',
                      ),
                      const SizedBox(height: 18),
                      FilledButton(
                        onPressed: isSubmitting
                            ? null
                            : () async {
                                final amount = double.tryParse(
                                  amountController.text.trim(),
                                );
                                if (amount == null || amount < 1) {
                                  _showMessage(
                                    'Enter a valid amount of at least GHS 1.00',
                                    isError: true,
                                  );
                                  return;
                                }

                                setSheetState(() => isSubmitting = true);

                                try {
                                  final result =
                                      await ApiService.requestDeposit(
                                    amount: amount,
                                    method: method,
                                    reference: referenceController.text
                                            .trim()
                                            .isEmpty
                                        ? null
                                        : referenceController.text.trim(),
                                    notes: notesController.text.trim().isEmpty
                                        ? null
                                        : notesController.text.trim(),
                                  );
                                  if (sheetContext.mounted) {
                                    Navigator.pop(sheetContext);
                                  }
                                  _showMessage(
                                    result['message']?.toString() ??
                                        'Deposit request submitted',
                                  );
                                  await _loadWallet();
                                } catch (e) {
                                  setSheetState(() => isSubmitting = false);
                                  _showMessage(e.toString(), isError: true);
                                }
                              },
                        style: FilledButton.styleFrom(
                          backgroundColor: _accent,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                        ),
                        child: isSubmitting
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                            : const Text('Submit request'),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );

    amountController.dispose();
    referenceController.dispose();
    notesController.dispose();
  }

  Future<void> _showWithdrawalSheet() async {
    final amountController = TextEditingController();
    final mobileController = TextEditingController();
    final bankNameController = TextEditingController();
    final accountNameController = TextEditingController();
    final accountNumberController = TextEditingController();
    var method = 'momo';
    var isSubmitting = false;
    final theme = Theme.of(context);
    final withdrawable = _summary?.wallet.balance ?? 0.0;
    final currency = _summary?.wallet.currency ?? 'GHS';

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: Colors.transparent,
      builder: (sheetContext) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            return Padding(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
              ),
              child: Container(
                decoration: BoxDecoration(
                  color: theme.colorScheme.surface,
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(28),
                  ),
                ),
                padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const _SheetHandle(),
                      const SizedBox(height: 20),
                      _SheetTitle(
                        icon: Icons.account_balance_rounded,
                        accent: _accent,
                        title: widget.isDriver
                            ? 'Cash out earnings'
                            : 'Withdraw funds',
                        subtitle:
                            'Withdrawable: $currency ${_money(withdrawable)} (bonus excluded).',
                      ),
                      const SizedBox(height: 20),
                      SegmentedButton<String>(
                        segments: const [
                          ButtonSegment(
                            value: 'momo',
                            icon: Icon(Icons.phone_android, size: 18),
                            label: Text('Mobile Money'),
                          ),
                          ButtonSegment(
                            value: 'bank',
                            icon: Icon(Icons.account_balance, size: 18),
                            label: Text('Bank'),
                          ),
                        ],
                        selected: {method},
                        showSelectedIcon: false,
                        onSelectionChanged: (value) =>
                            setSheetState(() => method = value.first),
                      ),
                      const SizedBox(height: 16),
                      TextField(
                        controller: amountController,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        decoration: InputDecoration(
                          labelText: 'Amount',
                          prefixText: '$currency ',
                          helperText:
                              'Available: $currency ${_money(withdrawable)}',
                          border: const OutlineInputBorder(),
                          suffixIcon: TextButton(
                            onPressed: () => setSheetState(
                              () => amountController.text =
                                  withdrawable.toStringAsFixed(2),
                            ),
                            child: const Text('Max'),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      if (method == 'momo')
                        TextField(
                          controller: mobileController,
                          keyboardType: TextInputType.phone,
                          decoration: const InputDecoration(
                            labelText: 'Mobile money number',
                            border: OutlineInputBorder(),
                          ),
                        )
                      else ...[
                        TextField(
                          controller: bankNameController,
                          decoration: const InputDecoration(
                            labelText: 'Bank name',
                            border: OutlineInputBorder(),
                          ),
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: accountNameController,
                          decoration: const InputDecoration(
                            labelText: 'Account name',
                            border: OutlineInputBorder(),
                          ),
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: accountNumberController,
                          decoration: const InputDecoration(
                            labelText: 'Account number',
                            border: OutlineInputBorder(),
                          ),
                        ),
                      ],
                      const SizedBox(height: 16),
                      const _InfoNote(
                        text:
                            'The amount is held until an administrator processes the request.',
                      ),
                      const SizedBox(height: 18),
                      FilledButton(
                        onPressed: isSubmitting
                            ? null
                            : () async {
                                final amount = double.tryParse(
                                  amountController.text.trim(),
                                );
                                if (amount == null || amount < 1) {
                                  _showMessage(
                                    'Enter a valid amount of at least $currency 1.00',
                                    isError: true,
                                  );
                                  return;
                                }
                                if (amount > withdrawable + 0.001) {
                                  _showMessage(
                                    'Amount exceeds your withdrawable balance.',
                                    isError: true,
                                  );
                                  return;
                                }

                                setSheetState(() => isSubmitting = true);

                                try {
                                  final result =
                                      await ApiService.requestWithdrawal(
                                    amount: amount,
                                    method: method,
                                    mobileNumber: method == 'momo'
                                        ? mobileController.text.trim()
                                        : null,
                                    bankName: method == 'bank'
                                        ? bankNameController.text.trim()
                                        : null,
                                    accountName: method == 'bank'
                                        ? accountNameController.text.trim()
                                        : null,
                                    accountNumber: method == 'bank'
                                        ? accountNumberController.text.trim()
                                        : null,
                                  );
                                  if (sheetContext.mounted) {
                                    Navigator.pop(sheetContext);
                                  }
                                  _showMessage(
                                    result['message']?.toString() ??
                                        'Withdrawal request submitted',
                                  );
                                  await _loadWallet();
                                } catch (e) {
                                  setSheetState(() => isSubmitting = false);
                                  _showMessage(e.toString(), isError: true);
                                }
                              },
                        style: FilledButton.styleFrom(
                          backgroundColor: _accent,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                        ),
                        child: isSubmitting
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                            : const Text('Submit request'),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );

    amountController.dispose();
    mobileController.dispose();
    bankNameController.dispose();
    accountNameController.dispose();
    accountNumberController.dispose();
  }

  void _showMessage(String message, {bool isError = false}) {
    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        behavior: SnackBarBehavior.floating,
        backgroundColor: isError ? _expense : _income,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final hasWallet = _summary != null;

    return Scaffold(
      extendBodyBehindAppBar: hasWallet,
      backgroundColor: theme.colorScheme.surface,
      appBar: AppBar(
        backgroundColor:
            hasWallet ? Colors.transparent : theme.colorScheme.surface,
        foregroundColor:
            hasWallet ? Colors.white : theme.colorScheme.onSurface,
        elevation: 0,
        scrolledUnderElevation: 0,
        title: Text(
          widget.isDriver ? 'Driver Wallet' : 'My Wallet',
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        actions: [
          IconButton(
            tooltip: 'Refresh',
            onPressed: _isLoading ? null : _loadWallet,
            icon: const Icon(Icons.refresh_rounded),
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: _isLoading
          ? _buildLoading()
          : _error != null
              ? _buildError()
              : _buildWallet(),
    );
  }

  Widget _buildLoading() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          CircularProgressIndicator(color: _accent),
          const SizedBox(height: 16),
          const Text(
            'Loading your wallet…',
            style: TextStyle(color: Colors.grey),
          ),
        ],
      ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: _expense.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.cloud_off_rounded,
                color: _expense,
                size: 34,
              ),
            ),
            const SizedBox(height: 16),
            const Text(
              'Could not load your wallet',
              style: TextStyle(fontSize: 17, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 6),
            Text(
              _error!,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 13, color: Colors.grey),
            ),
            const SizedBox(height: 20),
            FilledButton.icon(
              onPressed: _loadWallet,
              style: FilledButton.styleFrom(backgroundColor: _accent),
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Try again'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildWallet() {
    final summary = _summary!;
    final wallet = summary.wallet;
    final currency = wallet.currency;
    final theme = Theme.of(context);
    final pendingCount =
        summary.deposits.where((d) => d.status == 'pending').length +
            summary.withdrawals.where((w) => w.status == 'pending').length;

    return RefreshIndicator(
      color: _accent,
      onRefresh: _loadWallet,
      child: ListView(
        controller: _scrollController,
        padding: EdgeInsets.zero,
        children: [
          _buildHeader(wallet, currency),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 32),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (pendingCount > 0) ...[
                  _buildPendingSection(summary, currency),
                  const SizedBox(height: 24),
                ],
                KeyedSubtree(
                  key: _transactionsKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          const Expanded(
                            child: Text(
                              'Transactions',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                          Text(
                            '${summary.transactions.length} total',
                            style: TextStyle(
                              fontSize: 12,
                              color: theme.colorScheme.onSurfaceVariant,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Wrap(
                        spacing: 8,
                        children: [
                          _buildFilterChip(_TxFilter.all, 'All'),
                          _buildFilterChip(_TxFilter.moneyIn, 'Money in'),
                          _buildFilterChip(_TxFilter.moneyOut, 'Money out'),
                        ],
                      ),
                      const SizedBox(height: 14),
                      ..._buildTransactionList(
                        summary.transactions,
                        currency,
                        theme,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader(Wallet wallet, String currency) {
    final topPadding = MediaQuery.of(context).padding.top + kToolbarHeight + 4;

    return Container(
      clipBehavior: Clip.hardEdge,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: _gradient,
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(32)),
        boxShadow: [
          BoxShadow(
            color: _accent.withValues(alpha: 0.25),
            blurRadius: 24,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Stack(
        children: [
          Positioned(
            top: -70,
            right: -40,
            child: Container(
              width: 190,
              height: 190,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.07),
              ),
            ),
          ),
          Positioned(
            bottom: -80,
            left: -30,
            child: Container(
              width: 160,
              height: 160,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.05),
              ),
            ),
          ),
          Padding(
            padding: EdgeInsets.fromLTRB(20, topPadding, 20, 24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        widget.isDriver
                            ? 'Earnings available'
                            : 'Available balance',
                        style: const TextStyle(
                          color: Colors.white70,
                          fontSize: 13,
                          letterSpacing: 0.2,
                        ),
                      ),
                    ),
                    IconButton(
                      tooltip:
                          _hideBalance ? 'Show balance' : 'Hide balance',
                      onPressed: () =>
                          setState(() => _hideBalance = !_hideBalance),
                      icon: Icon(
                        _hideBalance
                            ? Icons.visibility_off_rounded
                            : Icons.visibility_rounded,
                      ),
                      color: Colors.white70,
                      iconSize: 20,
                      visualDensity: VisualDensity.compact,
                    ),
                  ],
                ),
                const SizedBox(height: 2),
                FittedBox(
                  fit: BoxFit.scaleDown,
                  alignment: Alignment.centerLeft,
                  child: Text(
                    _hideBalance
                        ? '••••••'
                        : '$currency ${_money(wallet.availableBalance)}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 38,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.5,
                      height: 1.1,
                    ),
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  widget.isDriver
                      ? 'From wallet-paid trips, bonuses and top-ups'
                      : 'Pay rides instantly and enjoy wallet bonuses',
                  style: const TextStyle(color: Colors.white70, fontSize: 12),
                ),
                const SizedBox(height: 20),
                Container(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.14),
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: Row(
                    children: [
                      _buildBalancePill('Main', wallet.balance, currency),
                      _buildHeaderDivider(),
                      _buildBalancePill('Bonus', wallet.bonusBalance, currency),
                      _buildHeaderDivider(),
                      _buildBalancePill(
                        'Pending',
                        wallet.pendingBalance,
                        currency,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 18),
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    children: [
                      _buildHeaderAction(
                        icon: Icons.add_card_rounded,
                        label: 'Add money',
                        onTap: _showDepositSheet,
                      ),
                      _buildHeaderDivider(),
                      _buildHeaderAction(
                        icon: Icons.account_balance_rounded,
                        label: widget.isDriver ? 'Cash out' : 'Withdraw',
                        onTap: _showWithdrawalSheet,
                      ),
                      _buildHeaderDivider(),
                      _buildHeaderAction(
                        icon: Icons.receipt_long_rounded,
                        label: 'History',
                        onTap: _scrollToTransactions,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBalancePill(String label, double value, String currency) {
    return Expanded(
      child: Column(
        children: [
          Text(
            label,
            style: const TextStyle(color: Colors.white70, fontSize: 11),
          ),
          const SizedBox(height: 4),
          FittedBox(
            fit: BoxFit.scaleDown,
            child: Text(
              _hideBalance ? '•••' : '$currency ${_money(value)}',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 13,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeaderDivider() {
    return Container(
      width: 1,
      height: 30,
      color: Colors.white.withValues(alpha: 0.18),
    );
  }

  Widget _buildHeaderAction({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
  }) {
    return Expanded(
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 10),
          child: Column(
            children: [
              Icon(icon, color: Colors.white, size: 22),
              const SizedBox(height: 6),
              Text(
                label,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 11.5,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPendingSection(WalletSummary summary, String currency) {
    final deposits = summary.deposits
        .where((deposit) => deposit.status == 'pending')
        .toList();
    final withdrawals = summary.withdrawals
        .where((withdrawal) => withdrawal.status == 'pending')
        .toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            const Expanded(
              child: Text(
                'Pending requests',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
              decoration: BoxDecoration(
                color: _pending.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                '${deposits.length + withdrawals.length} awaiting approval',
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: _pending,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        ...deposits.map(
          (deposit) => _buildRequestCard(
            icon: Icons.add_card_rounded,
            title: 'Top-up via ${_methodLabel(deposit.method)}',
            request: deposit,
            currency: currency,
          ),
        ),
        ...withdrawals.map(
          (withdrawal) => _buildRequestCard(
            icon: Icons.account_balance_rounded,
            title:
                '${widget.isDriver ? 'Payout' : 'Withdrawal'} to ${_methodLabel(withdrawal.method, isWithdrawal: true)}',
            request: withdrawal,
            currency: currency,
          ),
        ),
      ],
    );
  }

  Widget _buildRequestCard({
    required IconData icon,
    required String title,
    required WalletRequest request,
    required String currency,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: _pending.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: _pending.withValues(alpha: 0.22)),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: _pending.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, size: 20, color: _pending),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  _shortDate(request.createdAt),
                  style: TextStyle(
                    fontSize: 12,
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '$currency ${_money(request.amount)}',
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 4),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: _pending.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Text(
                  'PENDING',
                  style: TextStyle(
                    fontSize: 9,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 0.4,
                    color: _pending,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildFilterChip(_TxFilter value, String label) {
    final theme = Theme.of(context);
    final selected = _filter == value;

    return ChoiceChip(
      label: Text(label),
      selected: selected,
      showCheckmark: false,
      visualDensity: VisualDensity.compact,
      onSelected: (_) => setState(() => _filter = value),
      backgroundColor: Colors.transparent,
      selectedColor: _accent.withValues(alpha: 0.12),
      side: BorderSide(
        color: selected
            ? _accent.withValues(alpha: 0.45)
            : theme.colorScheme.outlineVariant,
      ),
      labelStyle: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w600,
        color: selected ? _accent : theme.colorScheme.onSurfaceVariant,
      ),
    );
  }

  List<Widget> _buildTransactionList(
    List<WalletTransaction> transactions,
    String currency,
    ThemeData theme,
  ) {
    final filtered = transactions.where((transaction) {
      switch (_filter) {
        case _TxFilter.moneyIn:
          return transaction.amount > 0;
        case _TxFilter.moneyOut:
          return transaction.amount < 0;
        case _TxFilter.all:
          return true;
      }
    }).toList();

    if (filtered.isEmpty) {
      return [_buildEmptyState(theme)];
    }

    final widgets = <Widget>[];
    String? currentDay;

    for (final transaction in filtered) {
      final day = _dayLabel(transaction.createdAt);
      if (day != currentDay) {
        if (widgets.isNotEmpty) {
          widgets.add(const SizedBox(height: 6));
        }
        widgets.add(
          Padding(
            padding: const EdgeInsets.fromLTRB(4, 8, 4, 6),
            child: Text(
              day,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                letterSpacing: 0.3,
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ),
        );
        currentDay = day;
      }
      widgets.add(_buildTransactionTile(transaction, currency, theme));
    }

    return widgets;
  }

  Widget _buildTransactionTile(
    WalletTransaction transaction,
    String currency,
    ThemeData theme,
  ) {
    final isCredit = transaction.amount >= 0;
    final color = isCredit ? _income : _expense;
    final sign = isCredit ? '+' : '-';

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withValues(alpha: 0.6),
        ),
      ),
      child: ListTile(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
        leading: Container(
          width: 42,
          height: 42,
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(_iconFor(transaction.type), color: color, size: 20),
        ),
        title: Text(
          transaction.description,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 2),
          child: Text(
            '${_typeLabel(transaction.type)} · ${_timeLabel(transaction.createdAt)}',
            style: TextStyle(
              fontSize: 12,
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(
              _hideBalance
                  ? '••••'
                  : '$sign$currency ${_money(transaction.amount.abs())}',
              style: TextStyle(
                color: color,
                fontSize: 14,
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              _hideBalance
                  ? '••••'
                  : 'Bal ${_money(transaction.balanceAfter)}',
              style: TextStyle(
                fontSize: 11,
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState(ThemeData theme) {
    final hasFilter = _filter != _TxFilter.all;

    return Container(
      padding: const EdgeInsets.symmetric(vertical: 36, horizontal: 24),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: theme.colorScheme.outlineVariant.withValues(alpha: 0.6),
        ),
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: _accent.withValues(alpha: 0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(
              hasFilter
                  ? Icons.filter_list_rounded
                  : Icons.account_balance_wallet_outlined,
              color: _accent,
              size: 30,
            ),
          ),
          const SizedBox(height: 14),
          Text(
            hasFilter ? 'Nothing in this filter' : 'No wallet activity yet',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 6),
          Text(
            hasFilter
                ? 'Try a different filter to see more transactions.'
                : widget.isDriver
                    ? 'Wallet-paid trips and bonuses will appear here.'
                    : 'Add money to pay for rides directly from your wallet.',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 13,
              height: 1.4,
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
          if (!hasFilter) ...[
            const SizedBox(height: 18),
            FilledButton.icon(
              onPressed: _showDepositSheet,
              style: FilledButton.styleFrom(backgroundColor: _accent),
              icon: const Icon(Icons.add_card_rounded),
              label: const Text('Add money'),
            ),
          ],
        ],
      ),
    );
  }

  IconData _iconFor(String type) {
    switch (type) {
      case 'deposit':
        return Icons.add_card_rounded;
      case 'bonus':
        return Icons.card_giftcard_rounded;
      case 'ride_payment':
        return Icons.directions_car_filled_rounded;
      case 'refund':
      case 'withdrawal_rejected':
        return Icons.replay_rounded;
      case 'adjustment':
        return Icons.tune_rounded;
      default:
        return type.startsWith('withdrawal')
            ? Icons.account_balance_rounded
            : Icons.receipt_long_rounded;
    }
  }

  String _typeLabel(String type) {
    switch (type) {
      case 'deposit':
        return 'Top-up';
      case 'bonus':
        return 'Bonus';
      case 'ride_payment':
        return 'Ride payment';
      case 'refund':
        return 'Refund';
      case 'withdrawal_hold':
        return 'Withdrawal';
      case 'withdrawal_rejected':
        return 'Returned to wallet';
      case 'withdrawal_paid':
        return 'Payout';
      case 'adjustment':
        return 'Adjustment';
      case 'refund_reversal':
        return 'Refund reversal';
      default:
        return type.replaceAll('_', ' ');
    }
  }

  String _methodLabel(String method, {bool isWithdrawal = false}) {
    if (method == 'momo') return 'Mobile Money';
    return isWithdrawal ? 'bank account' : 'bank transfer';
  }

  String _money(double value) {
    final fixed = value.abs().toStringAsFixed(2);
    final parts = fixed.split('.');
    final digits = parts[0];
    final buffer = StringBuffer();
    for (var i = 0; i < digits.length; i++) {
      if (i > 0 && (digits.length - i) % 3 == 0) {
        buffer.write(',');
      }
      buffer.write(digits[i]);
    }
    return '$buffer.${parts[1]}';
  }

  String _dayLabel(DateTime? date) {
    if (date == null) return 'Earlier';
    final local = date.toLocal();
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final day = DateTime(local.year, local.month, local.day);
    final difference = today.difference(day).inDays;

    if (difference == 0) return 'Today';
    if (difference == 1) return 'Yesterday';

    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];
    return '${local.day} ${months[local.month - 1]} ${local.year}';
  }

  String _timeLabel(DateTime? date) {
    if (date == null) return '—';
    final local = date.toLocal();
    final hour = local.hour.toString().padLeft(2, '0');
    final minute = local.minute.toString().padLeft(2, '0');
    return '$hour:$minute';
  }

  String _shortDate(DateTime? date) {
    if (date == null) return '';
    final local = date.toLocal();
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];
    return '${local.day} ${months[local.month - 1]} · ${_timeLabel(date)}';
  }
}

class _SheetHandle extends StatelessWidget {
  const _SheetHandle();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Container(
        width: 44,
        height: 4,
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.outlineVariant,
          borderRadius: BorderRadius.circular(2),
        ),
      ),
    );
  }
}

class _SheetTitle extends StatelessWidget {
  final IconData icon;
  final Color accent;
  final String title;
  final String subtitle;

  const _SheetTitle({
    required this.icon,
    required this.accent,
    required this.title,
    required this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: accent.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(14),
          ),
          child: Icon(icon, color: accent, size: 22),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontSize: 19,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                style: TextStyle(
                  fontSize: 12,
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _InfoNote extends StatelessWidget {
  final String text;

  const _InfoNote({required this.text});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest.withValues(alpha: 0.5),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            Icons.info_outline_rounded,
            size: 18,
            color: theme.colorScheme.onSurfaceVariant,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                fontSize: 12,
                height: 1.4,
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
