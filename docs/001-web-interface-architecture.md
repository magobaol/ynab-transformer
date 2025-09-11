# ADR-001: Web Interface Architecture for YNAB Transformer

## Status
**Proposed** - 2025-09-09

## Context
The YNAB Transformer currently exists as a console-only application that converts bank statement files from various Italian financial institutions into YNAB-compatible CSV format. Users must manually specify the bank format via a `--format` parameter, which creates friction for non-technical users.

### Business Requirements
- Add a web interface to make the tool accessible to non-technical users
- Enhance console functionality with automatic format detection
- Maintain backward compatibility with existing console functionality
- Deploy as a subdirectory application within an existing domain (`/ynab-transformer/`)
- Provide automatic format detection to eliminate user confusion
- Ensure security and abuse prevention for public hosting
- Maintain privacy by not storing user transaction data

### Technical Constraints
- PHP 8.4+ environment
- Existing Symfony console application architecture
- Subdirectory hosting requirement
- Traditional web hosting compatibility
- File size practical limit of 1MB for typical bank statements

## Decision

### Architecture Pattern: Service Layer Extraction
We will implement a **service-oriented architecture** that extracts business logic into reusable services, allowing both console and web interfaces to utilize the same core functionality.

**Rationale**: This approach avoids code duplication, enhances the existing console application with auto-detection, and creates a clean foundation for web functionality.

### Auto-Detection Strategy: Full Structure Analysis
We will implement format detection by having each transformer test if it can handle a given file:
- Each transformer implements `canHandle(string $filename): bool`
- Factory tests formats in popularity order: `['fineco', 'revolut', 'nexi', 'popso', 'poste', 'telepass', 'isybank']`
- Ensures single format match (throws exception for ambiguous files)
- Provides user-friendly hints when no format detected

**Rationale**: Performance impact is acceptable for typical small files (<500 transactions). This approach is more reliable than header-only analysis and reuses existing transformation logic.

### Console Command Enhancement: Optional Auto-Detection
The console command will be enhanced to support automatic format detection while maintaining full backward compatibility:
- Make `--format` parameter optional (currently required)
- When `--format` not provided: use `TransformerFactory::detectFormat()` for auto-detection
- When `--format` provided: maintain existing behavior (backward compatibility)
- Display detected format to user for transparency

**Rationale**: This provides immediate value to console users, validates the auto-detection system in real-world usage, and maintains complete backward compatibility for existing scripts and workflows.

### Frontend Technology Stack
- **Alpine.js**: Lightweight reactive framework for all client-side functionality
- **Bootstrap 5**: Component library for consistent, professional styling
- **Single Page Application**: Minimal interface focused on drag-and-drop file upload

**Rationale**: Alpine.js provides modern reactive capabilities without the complexity of larger frameworks. Bootstrap ensures professional appearance without requiring design expertise.

### Security & Abuse Prevention
- **Rate Limiting**: 5 files per IP per 10 minutes with whitelist capability for testing
- **File Validation**: Server-side type and size validation (1MB limit)
- **Privacy Protection**: Immediate file deletion after processing
- **Comprehensive Logging**: Detailed audit trail without storing transaction content

**Rationale**: Balanced approach between usability and protection against abuse while maintaining strict privacy standards.

### Deployment Architecture
The application will be self-contained within a subdirectory:
```
somedomain.com/
├── (other content)
└── ynab-transformer/          # Symfony application root
    ├── public/
    │   ├── index.php          # Symfony entry point
    │   └── privacy.html       # Static privacy policy
    ├── src/
    ├── config/
    └── ...                    # Standard Symfony structure
```

## Consequences

### Positive Consequences
- **Accessibility**: Non-technical users can utilize the tool through web interface
- **Usability**: Automatic format detection eliminates user confusion for both console and web users
- **Console Enhancement**: Existing console users benefit from auto-detection without breaking changes
- **Maintainability**: Service layer architecture promotes code reuse and testing
- **Security**: Comprehensive protection against abuse while maintaining privacy
- **Scalability**: Architecture supports future enhancements and additional formats
- **Mobile Support**: Responsive design accommodates tablet/mobile usage

### Negative Consequences
- **Complexity**: Additional web layer increases codebase complexity
- **Hosting Requirements**: Requires web server configuration and maintenance
- **Security Surface**: Web interface introduces new attack vectors requiring monitoring
- **Resource Usage**: Server processing of uploaded files requires resource management

### Mitigation Strategies
- **Testing**: Comprehensive test suite for both console and web functionality
- **Documentation**: Detailed developer documentation for maintenance and enhancement
- **Monitoring**: Health check endpoint and log rotation for operational visibility
- **Gradual Rollout**: Enhance console application first, then add web interface
- **Backward Compatibility**: All existing console usage patterns continue to work unchanged

## Alternatives Considered

### Alternative 1: API-First Architecture
**Rejected**: Adds unnecessary complexity for a simple single-purpose application.

### Alternative 2: Filename-Based Format Detection
**Rejected**: Less reliable as users often rename files, provides poor user experience for edge cases.

### Alternative 3: Minimal Web Layer Without Service Extraction
**Rejected**: Creates code duplication and reduces maintainability for future enhancements.

### Alternative 4: React/Vue Frontend
**Rejected**: Overkill for single-page functionality, adds build complexity and deployment requirements.

## Related Documents
- [002-web-interface-implementation-plan.md](./002-web-interface-implementation-plan.md) - Detailed technical implementation guide and development phases

## Related Decisions
- Console application enhanced with auto-detection while maintaining full backward compatibility
- Privacy-first approach with immediate file deletion
- Traditional web hosting compatibility maintained
- Bootstrap default styling approach for rapid development

---

**Author**: Francesco Face  
**Reviewers**: Francesco Face  
**Implementation Target**: Q4 2025
