export type PackageSlug = 'starter' | 'standard' | 'premium';

export interface PackageDto {
  slug: PackageSlug;
  name: string;
  oneShotPrice: number;
  monthlyPrice: number;
  maxPages: number;
  features: PackageFeature[];
  highlighted: boolean;
}

export interface PackageFeature {
  label: string;
  included: boolean;
}

export interface ContactPayload {
  name: string;
  email: string;
  message: string;
  website?: string;
}

export interface QuotePayload {
  package: PackageSlug;
  clientName: string;
  clientCompany?: string;
  clientEmail: string;
  clientPhone: string;
  clientAddress: string;
  projectDescription: string;
  options?: Record<string, number>;
  website?: string;
  locale?: 'fr' | 'en';
}

export interface QuoteCreatedResponse {
  id: string;
  checkoutUrl: string;
  totalOneShot: number;
  totalMonthly: number;
}

export interface QuoteSummary {
  id: string;
  package: PackageSlug;
  clientName: string;
  clientEmail: string;
  status: 'pending' | 'deposit_paid' | 'active' | 'declined' | 'cancelled';
  totalOneShot: number;
  totalMonthly: number;
  createdAt: string;
}

export interface ContactMessageDto {
  id: number;
  name: string;
  email: string;
  message: string;
  status: 'unread' | 'read' | 'archived';
  createdAt: string;
}

export interface ProfileDto {
  bio: string;
  stack: string[];
  links: Record<string, string>;
  photoUrl: string | null;
}

export interface AdminUserDto {
  email: string;
  roles: string[];
}
