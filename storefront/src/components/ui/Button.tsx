import React from 'react';

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'primary' | 'secondary' | 'outline' | 'accent' | 'ghost';
  size?: 'sm' | 'md' | 'lg' | 'icon';
  isLoading?: boolean;
}

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  (
    {
      className = '',
      variant = 'primary',
      size = 'md',
      isLoading = false,
      children,
      disabled,
      ...props
    },
    ref
  ) => {
    const base =
      'inline-flex items-center justify-center font-display font-bold uppercase tracking-[0.15em] transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:pointer-events-none disabled:opacity-40';

    const variants = {
      primary:   'bg-foreground text-background hover:bg-foreground/90',
      secondary: 'bg-surface-2 text-foreground hover:bg-border',
      outline:   'border border-border text-foreground hover:border-foreground hover:bg-surface',
      accent:    'bg-accent text-black hover:bg-accent/90',
      ghost:     'text-muted hover:text-foreground hover:bg-surface',
    };

    const sizes = {
      sm:   'h-8 px-4 text-[10px]',
      md:   'h-10 px-6 text-xs',
      lg:   'h-12 px-8 text-xs',
      icon: 'h-10 w-10',
    };

    return (
      <button
        ref={ref}
        disabled={disabled || isLoading}
        className={`${base} ${variants[variant]} ${sizes[size]} ${className}`}
        {...props}
      >
        {isLoading && (
          <span className="mr-2 h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent" />
        )}
        {children}
      </button>
    );
  }
);

Button.displayName = 'Button';
