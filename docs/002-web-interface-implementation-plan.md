# 002: Web Interface Implementation Plan

## Document Information
- **Type**: Implementation Plan
- **Related ADR**: [001-web-interface-architecture.md](./001-web-interface-architecture.md)
- **Date**: 2025-09-09
- **Status**: Ready for Implementation

## Overview
This document provides detailed technical implementation steps for adding a web interface to the YNAB Transformer application, as decided in ADR-001. The implementation maintains backward compatibility with the existing console application while adding automatic format detection and a user-friendly web interface.

---

## **Phase 1: Backend Architecture Changes**

### **1.1 Transformer Interface Enhancement**
- Add `canHandle(string $filename): bool` method to `Transformer` interface
- Implement `canHandle()` in all transformer classes:
  - Use existing transformation logic to test file compatibility
  - Return `true` if file can be processed, `false` if errors occur
  - Handle exceptions gracefully during detection

### **1.2 Service Layer Extraction**
- **Create `TransformationService`**:
  - Orchestrates the transformation process
  - Uses TransformerFactory for auto-detection
  - Coordinates with FileProcessingService
  
- **Create `FileProcessingService`**:
  - Handles temporary file storage for uploads
  - Generates CSV download responses
  - Manages file cleanup (immediate deletion after processing)
  
- **Create `TransformerFactory`**:
  - Implements auto-detection logic using `canHandle()` methods
  - Tests formats in popularity order: `['fineco', 'revolut', 'nexi', 'popso', 'poste', 'telepass', 'isybank']`
  - Ensures only one format matches (throws exception if multiple matches)
  - Provides hints when no format detected

### **1.3 Web Dependencies**
- Add Symfony HTTP components to `composer.json`:
  - `symfony/http-foundation`
  - `symfony/http-kernel`
  - Required routing and controller components

---

## **Phase 2: Web Controller Implementation**

### **2.1 Main Controller**
- **Create `TransformController`**:
  - `GET /`: Serves the main web interface (root of Symfony app)
  - `POST /transform`: Handles file upload and processing
  - `GET /health`: Returns simple JSON status for monitoring
  - Uses dependency injection for services
  - Implements comprehensive error handling

### **2.2 File Upload Handling**
- Accept multipart form uploads (1MB file size limit)
- Validate file types (Excel/CSV only)
- Store uploads temporarily on disk
- Process files using `TransformationService`
- Return direct file download response for CSV output
- Always delete uploaded files after processing (privacy)

### **2.3 Error Response System**
- Return JSON error responses for AJAX requests
- Generic user messages (detailed errors only in logs)
- Specific error types:
  - File format not recognized (with hints)
  - Multiple formats detected
  - Processing errors
  - Upload errors

---

## **Phase 3: Frontend Implementation**

### **3.1 Technology Stack**
- **Alpine.js**: For all reactive functionality including file upload handling
- **Bootstrap 5**: For styling and components

### **3.2 User Interface**
- Single page application at `/` (root of Symfony app within `/ynab-transformer/` subdirectory)
- Large drag-and-drop zone as primary interface
- "Browse Files" button for mobile/tablet compatibility
- Visual feedback during processing (loading indicator)
- Error banner system below drop zone
- Success banner: "Thank you for using this service!" after file download
- Privacy policy link at bottom pointing to `./privacy.html` (static file)
- Responsive design for desktop/tablet/mobile

### **3.3 File Upload Flow**
- Immediate processing on file drop/selection
- **Single upload prevention**: Disable drop zone and browse button while processing
- Client-side file validation (size/type)
- AJAX upload with progress indication
- Automatic download trigger on success
- "Thank you" banner appears after download, auto-hides after 3-4 seconds
- Error display for failures
- Re-enable interface after completion or error

---

## **Phase 4: Security & Abuse Prevention**

### **4.1 Rate Limiting**
- Implement IP-based rate limiting: 5 files per 10 minutes
- IP whitelist system for testing (bypass rate limits completely)
- Store rate limit data in temporary files/cache

### **4.2 Input Validation**
- Server-side file type validation
- File size enforcement (1MB limit)
- Input sanitization for all user data
- Secure temporary file handling

### **4.3 Security Headers**
- Implement basic security headers
- CSRF protection for forms
- Secure file upload handling

---

## **Phase 5: Logging & Monitoring**

### **5.1 Comprehensive Logging**
- **Basic data**: Timestamp, IP, file size, success/failure, processing time
- **Additional details**: Original filename, detected format, transaction count, error details, user agent
- Store in log files (not database)

### **5.2 Log Management**
- **Log rotation**: Automatic cleanup of log files older than 30 days
- Prevent unlimited disk space usage from logs
- Configurable retention period
- Rotate logs to prevent disk space issues

### **5.3 Privacy Compliance**
- Log IP addresses as provided
- Never log transaction content/data
- Immediate file deletion after processing
- Clear temporary file cleanup

---

## **Phase 6: Configuration & Deployment**

### **6.1 Environment Configuration**
- Add web-specific configuration options
- File upload path configuration
- Rate limiting configuration
- Logging configuration

### **6.2 Routing Setup**
- Configure Symfony to work within subdirectory deployment (`/ynab-transformer/`)
- Root route (`/`) serves the main transform interface
- Proper base path configuration for subdirectory hosting
- Maintain existing console command functionality

### **6.3 Static Files**
- Create `public/privacy.html` with privacy policy covering:
  - No data storage policy
  - Temporary processing consent
  - Logging practices (no transaction content)
  - Liability limitation clauses
  - Contact information

---

## **Phase 7: Testing & Documentation**

### **7.1 Automated Testing**
- Unit tests for new services
- Integration tests for web endpoints
- File upload testing
- Error handling verification
- Health check endpoint testing

### **7.2 Manual Testing**
- Cross-browser compatibility testing
- Mobile/tablet usability testing
- Performance testing with various file sizes
- Security testing (rate limiting, file validation)

### **7.3 Developer Documentation**
- **Local development setup instructions**
- **Project structure explanation**
- **How to add new transformer formats**
- **Deployment guide for subdirectory hosting**
- **Environment configuration options**
- **Health monitoring setup**
- **Log management procedures**

---

## **Technical Requirements Summary**

### **Backend Requirements:**
- PHP 8.4+
- Existing Symfony console application structure
- File system write permissions for temporary files and logs
- Web server configuration for Symfony applications in subdirectories

### **Frontend Requirements:**
- Modern browser with JavaScript enabled
- File API support for drag-and-drop
- Bootstrap 5 compatible browsers

### **Hosting Requirements:**
- PHP 8.4+ with required extensions
- Write permissions for temporary files and logs
- Standard web hosting or VPS capabilities
- Subdirectory hosting configuration
- HTTPS recommended for production

---

## **Deployment Structure**
```
somedomain.com/
├── (other content)
└── ynab-transformer/          # Symfony application root
    ├── public/
    │   ├── index.php          # Symfony entry point
    │   └── privacy.html       # Static privacy policy
    ├── src/
    ├── config/
    └── ...                    # Rest of Symfony structure
```

---

## **Deliverables**
1. Enhanced Symfony application with web interface
2. Unchanged console functionality  
3. Comprehensive logging system with rotation
4. Security and rate limiting features
5. Mobile-responsive web interface with single-upload UX
6. Health monitoring endpoint
7. Static privacy policy page
8. Complete developer documentation
9. Subdirectory deployment configuration

---

**This plan maintains backward compatibility while adding robust web functionality with proper security, logging, monitoring, and user experience considerations optimized for subdirectory hosting.**