class Wallet {
  final double balance;
  final double bonusBalance;
  final double pendingBalance;
  final double availableBalance;
  final String currency;
  final DateTime? lastTransactionAt;

  Wallet({
    required this.balance,
    required this.bonusBalance,
    required this.pendingBalance,
    required this.availableBalance,
    required this.currency,
    this.lastTransactionAt,
  });

  factory Wallet.fromJson(Map<String, dynamic> json) {
    return Wallet(
      balance: _asDouble(json['balance']),
      bonusBalance: _asDouble(json['bonus_balance']),
      pendingBalance: _asDouble(json['pending_balance']),
      availableBalance: _asDouble(json['available_balance']),
      currency: json['currency'] ?? 'GHS',
      lastTransactionAt: json['last_transaction_at'] != null
          ? DateTime.tryParse(json['last_transaction_at'].toString())
          : null,
    );
  }
}

class WalletTransaction {
  final int id;
  final String type;
  final double amount;
  final double balanceAfter;
  final String description;
  final String? referenceType;
  final int? referenceId;
  final DateTime? createdAt;

  WalletTransaction({
    required this.id,
    required this.type,
    required this.amount,
    required this.balanceAfter,
    required this.description,
    this.referenceType,
    this.referenceId,
    this.createdAt,
  });

  factory WalletTransaction.fromJson(Map<String, dynamic> json) {
    return WalletTransaction(
      id: json['id'],
      type: json['type'] ?? 'adjustment',
      amount: _asDouble(json['amount']),
      balanceAfter: _asDouble(json['balance_after']),
      description: json['description'] ?? '',
      referenceType: json['reference_type'],
      referenceId: json['reference_id'],
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }
}

class WalletRequest {
  final int id;
  final double amount;
  final String method;
  final String status;
  final String? reference;
  final String? notes;
  final DateTime? createdAt;

  WalletRequest({
    required this.id,
    required this.amount,
    required this.method,
    required this.status,
    this.reference,
    this.notes,
    this.createdAt,
  });

  factory WalletRequest.fromJson(Map<String, dynamic> json) {
    return WalletRequest(
      id: json['id'],
      amount: _asDouble(json['amount']),
      method: json['method'] ?? '',
      status: json['status'] ?? 'pending',
      reference: json['reference'],
      notes: json['notes'],
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }
}

class WalletSummary {
  final Wallet wallet;
  final List<WalletTransaction> transactions;
  final List<WalletRequest> deposits;
  final List<WalletRequest> withdrawals;

  WalletSummary({
    required this.wallet,
    required this.transactions,
    required this.deposits,
    required this.withdrawals,
  });

  factory WalletSummary.fromJson(Map<String, dynamic> json) {
    return WalletSummary(
      wallet: Wallet.fromJson(json['wallet'] as Map<String, dynamic>),
      transactions: (json['transactions'] as List<dynamic>? ?? [])
          .map((item) => WalletTransaction.fromJson(item as Map<String, dynamic>))
          .toList(),
      deposits: (json['deposits'] as List<dynamic>? ?? [])
          .map((item) => WalletRequest.fromJson(item as Map<String, dynamic>))
          .toList(),
      withdrawals: (json['withdrawals'] as List<dynamic>? ?? [])
          .map((item) => WalletRequest.fromJson(item as Map<String, dynamic>))
          .toList(),
    );
  }
}

double _asDouble(dynamic value) {
  if (value == null) return 0.0;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString()) ?? 0.0;
}
